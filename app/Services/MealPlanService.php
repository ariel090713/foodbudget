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

    public function generatePlan(array $params, User $user): MealPlan
    {
        // Free tier enforcement
        if (! $this->subscriptionService->isActive($user) && $params['numberOfDays'] > config('budgetbite.free_tier_max_days', 1)) {
            throw new AccessDeniedHttpException('Subscription required for multi-day plans.');
        }

        $dailyBudgetPerPerson = $this->calculateDailyBudget(
            $params['totalBudget'],
            $params['numberOfDays'],
            $params['numberOfPersons'],
        );

        $tier = $params['preferredTier']
            ?? $this->tierService->detectTier($dailyBudgetPerPerson, $params['countryCode']);

        // Check if budget is extremely low
        $thresholds = $this->tierService->getThresholds($params['countryCode']);
        $isExtremelyLow = $dailyBudgetPerPerson < ($thresholds['poor_min'] * config('budgetbite.basic_meal_threshold_multiplier', 0.5));

        try {
            $aiResponse = $this->openAIService->generateMealPlan([
                'totalBudget' => $params['totalBudget'],
                'dailyBudgetPerPerson' => round($dailyBudgetPerPerson, 2),
                'currencyCode' => $params['currencyCode'],
                'numberOfDays' => $params['numberOfDays'],
                'numberOfPersons' => $params['numberOfPersons'],
                'startDate' => $params['startDate'],
                'countryCode' => $params['countryCode'],
                'economicTier' => $tier,
                'skippedMealTypes' => $params['skippedMealTypes'] ?? [],
                'isExtremelyLowBudget' => $isExtremelyLow,
            ]);
        } catch (\Throwable $e) {
            Log::error('Meal plan generation failed', ['message' => $e->getMessage()]);
            throw new HttpException(503, 'Meal plan generation is temporarily unavailable.');
        }

        // Build days with proper dates
        $startDate = Carbon::parse($params['startDate']);
        $days = [];
        $totalCost = 0;

        foreach ($aiResponse['days'] ?? [] as $i => $day) {
            $day['dayIndex'] = $i;
            $day['date'] = $startDate->copy()->addDays($i)->toDateString();
            $dailyCost = (float) ($day['dailyCost'] ?? 0);
            $totalCost += $dailyCost;
            $days[] = $day;
        }

        // Clamp totalCost to budget
        $totalCost = min($totalCost, $params['totalBudget']);
        $remainingBudget = max(0, $params['totalBudget'] - $totalCost);

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
            'days_json' => $days,
            'total_cost' => $totalCost,
            'remaining_budget' => $remainingBudget,
            'detected_tier' => $tier,
        ]);

        return $mealPlan;
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

        // Allow a bit more budget from remaining
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

        // Ensure structure
        $newDay['dayIndex'] = $dayIndex;
        $newDay['date'] = $originalDay['date'] ?? Carbon::parse($request['startDate'])->addDays($dayIndex)->toDateString();

        // Update the plan
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
