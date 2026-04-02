<?php

namespace App\Services;

use App\Models\MealPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class MealPlanService
{
    public function __construct(
        private OpenAIService $openAIService,
        private TierService $tierService,
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * Create a meal plan record and dispatch background generation.
     */
    public function createPlan(array $params, User $user): MealPlan
    {
        // Free tier enforcement
        if (! $this->subscriptionService->isActive($user)) {
            if ($params['numberOfDays'] > config('budgetbite.free_tier_max_days', 1)) {
                throw new AccessDeniedHttpException('Subscription required for multi-day plans.');
            }
            if ($params['numberOfPersons'] > config('budgetbite.free_tier_max_persons', 1)) {
                throw new AccessDeniedHttpException('Subscription required for multi-person plans. Free tier supports 1 person only.');
            }
        }

        // Free tier: max saved plans limit
        if (! $this->subscriptionService->isActive($user)) {
            $maxPlans = config('budgetbite.rate_limits.free_max_saved_plans', 5);
            $currentPlans = MealPlan::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count();

            if ($currentPlans >= $maxPlans) {
                throw new AccessDeniedHttpException(
                    "Free tier limit: you can save up to {$maxPlans} meal plans. Delete old plans or upgrade to premium for unlimited plans."
                );
            }
        }

        $dailyBudgetPerPerson = $this->calculateDailyBudget(
            $params['totalBudget'],
            $params['numberOfDays'],
            $params['numberOfPersons'],
        );

        // Minimum budget check
        $activeMeals = 3 - count($params['skippedMealTypes'] ?? []);
        $minBudgetPerPersonPerDay = $activeMeals * 5;
        if ($dailyBudgetPerPerson < $minBudgetPerPersonPerDay && $activeMeals > 0) {
            $minTotal = $minBudgetPerPersonPerDay * $params['numberOfDays'] * $params['numberOfPersons'];
            throw new UnprocessableEntityHttpException(
                "Budget too low. You need at least {$minTotal} {$params['currencyCode']} "
                . "for {$params['numberOfDays']} day(s) and {$params['numberOfPersons']} person(s) "
                . "({$activeMeals} meals/day). Try increasing your budget, reducing days, or reducing persons."
            );
        }

        $tier = $params['preferredTier']
            ?? $this->tierService->detectTier($dailyBudgetPerPerson, $params['countryCode']);

        // Create placeholder record
        $mealPlan = MealPlan::create([
            'user_id' => $user->id,
            'request_json' => [
                'totalBudget' => $params['totalBudget'],
                'currencyCode' => $params['currencyCode'],
                'numberOfDays' => $params['numberOfDays'],
                'numberOfPersons' => $params['numberOfPersons'],
                'startDate' => $params['startDate'],
                'countryCode' => $params['countryCode'],
                'preferredTier' => $params['preferredTier'] ?? null,
                'skippedMealTypes' => $params['skippedMealTypes'] ?? [],
            ],
            'days_json' => [],
            'total_cost' => 0,
            'remaining_budget' => $params['totalBudget'],
            'detected_tier' => $tier,
            'status' => 'generating',
        ]);

        // Dispatch background job
        \App\Jobs\GenerateMealPlanJob::dispatch(
            $mealPlan->id,
            $params,
            $user->id,
        );

        return $mealPlan;
    }

    /**
     * Process the actual AI generation in chunks (called from background job).
     * Splits large plans into 7-day batches for reliable generation.
     */
    public function processGenerationChunked(MealPlan $plan, array $params, User $user): void
    {
        $totalDays = $params['numberOfDays'];
        // 3-day chunks: fast results (~8 sec each) + efficient API usage
        $chunkSize = min(3, $totalDays);
        $allDays = [];
        $totalCost = 0;

        $dailyBudgetPerPerson = $this->calculateDailyBudget(
            $params['totalBudget'],
            $params['numberOfDays'],
            $params['numberOfPersons'],
        );

        $tier = $params['preferredTier']
            ?? $this->tierService->detectTier($dailyBudgetPerPerson, $params['countryCode']);

        $thresholds = $this->tierService->getThresholds($params['countryCode']);
        $isExtremelyLow = $dailyBudgetPerPerson < ($thresholds['poor_min'] * config('budgetbite.basic_meal_threshold_multiplier', 0.5));
        $isPremium = $this->subscriptionService->isActive($user);

        $startDate = \Carbon\Carbon::parse($params['startDate']);
        $remainingBudget = $params['totalBudget'];

        // Generate in chunks of 7 days
        for ($offset = 0; $offset < $totalDays; $offset += $chunkSize) {
            $daysInChunk = min($chunkSize, $totalDays - $offset);
            $chunkStartDate = $startDate->copy()->addDays($offset);
            $chunkNum = (int) ($offset / $chunkSize) + 1;

            Log::info("Generating chunk {$chunkNum}: days {$offset}-" . ($offset + $daysInChunk - 1) . " for plan {$plan->id}");
            $chunkStart = microtime(true);

            // Budget for this chunk = proportional share of remaining budget
            $daysLeft = $totalDays - $offset;
            $chunkBudget = round(($remainingBudget / $daysLeft) * $daysInChunk, 2);

            // Collect previous day meals for variety context
            $previousMeals = [];
            $lookback = array_slice($allDays, -2);
            foreach ($lookback as $day) {
                foreach ($day['meals'] ?? [] as $meal) {
                    if (! ($meal['isSkipped'] ?? false)) {
                        $previousMeals[] = $meal['name'] ?? '';
                    }
                }
            }
            $previousMealsNote = ! empty($previousMeals)
                ? 'Meals already used in previous days (DO NOT repeat these): ' . implode(', ', $previousMeals)
                : '';

            $aiResponse = $this->openAIService->generateMealPlan([
                'totalBudget' => $chunkBudget,
                'dailyBudgetPerPerson' => round($dailyBudgetPerPerson, 2),
                'currencyCode' => $params['currencyCode'],
                'numberOfDays' => $daysInChunk,
                'numberOfPersons' => $params['numberOfPersons'],
                'startDate' => $chunkStartDate->toDateString(),
                'countryCode' => $params['countryCode'],
                'economicTier' => $tier,
                'skippedMealTypes' => $params['skippedMealTypes'] ?? [],
                'isExtremelyLowBudget' => $isExtremelyLow,
                'includePremiumData' => $isPremium,
                'previousMealsNote' => $previousMealsNote,
            ]);

            $chunkCost = 0;
            foreach ($aiResponse['days'] ?? [] as $i => $day) {
                $globalIndex = $offset + $i;
                $day['dayIndex'] = $globalIndex;
                $day['date'] = $startDate->copy()->addDays($globalIndex)->toDateString();
                $dailyCost = (float) ($day['dailyCost'] ?? 0);
                $chunkCost += $dailyCost;
                $allDays[] = $day;
            }

            $totalCost += $chunkCost;
            $remainingBudget -= $chunkCost;

            $elapsed = round(microtime(true) - $chunkStart, 1);
            Log::info("Chunk {$chunkNum} done in {$elapsed}s — {$daysInChunk} days, cost: {$chunkCost}");

            // Update plan progressively so Flutter can show partial results
            $plan->update([
                'days_json' => $allDays,
                'total_cost' => min($totalCost, $params['totalBudget']),
                'remaining_budget' => max(0, $params['totalBudget'] - $totalCost),
                'detected_tier' => $tier,
            ]);
        }

        $plan->update(['status' => 'completed']);
    }

    public function regenerateDay(MealPlan $plan, int $dayIndex): array
    {
        $days = $plan->days_json;
        $request = $plan->request_json;

        if ($dayIndex < 0 || $dayIndex >= count($days)) {
            throw new UnprocessableEntityHttpException('Day index out of range.');
        }

        $originalDay = $days[$dayIndex];
        $dailyBudget = (float) ($originalDay['dailyCost'] ?? 0);
        $availableBudget = $dailyBudget + (float) $plan->remaining_budget;

        try {
            $newDay = $this->openAIService->regenerateDay([
                'dayIndex' => $dayIndex,
                'numberOfPersons' => $request['numberOfPersons'],
                'countryCode' => $request['countryCode'],
                'currencyCode' => $request['currencyCode'],
                'dailyBudget' => round($availableBudget, 2),
                'economicTier' => $plan->detected_tier,
                'date' => $originalDay['date'] ?? Carbon::parse($request['startDate'])->addDays($dayIndex)->toDateString(),
                'skippedMealTypes' => implode(', ', $request['skippedMealTypes'] ?? []) ?: 'none',
            ], $originalDay);
        } catch (\Throwable $e) {
            Log::error('Day regeneration failed', ['message' => $e->getMessage()]);
            throw new HttpException(503, 'Meal plan generation is temporarily unavailable.');
        }

        $newDay['dayIndex'] = $dayIndex;
        $newDay['date'] = $originalDay['date'] ?? Carbon::parse($request['startDate'])->addDays($dayIndex)->toDateString();

        $days[$dayIndex] = $newDay;
        $newTotalCost = array_sum(array_column($days, 'dailyCost'));
        $newTotalCost = min($newTotalCost, $request['totalBudget']);

        $plan->update([
            'days_json' => $days,
            'total_cost' => $newTotalCost,
            'remaining_budget' => max(0, $request['totalBudget'] - $newTotalCost),
        ]);

        return $newDay;
    }

    public function calculateDailyBudget(float $totalBudget, int $days, int $persons): float
    {
        return $totalBudget / ($days * $persons);
    }
}
