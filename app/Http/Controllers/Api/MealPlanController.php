<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GenerateMealPlanRequest;
use App\Http\Resources\MealPlanResource;
use App\Models\MealPlan;
use App\Services\MealPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealPlanController extends Controller
{
    public function __construct(
        private MealPlanService $mealPlanService,
    ) {}

    public function store(GenerateMealPlanRequest $request): JsonResponse
    {
        $plan = $this->mealPlanService->createPlan(
            $request->validated(),
            $request->user(),
        );

        return (new MealPlanResource($plan->load('user')))
            ->response()
            ->setStatusCode(202); // Accepted — processing in background
    }

    public function show(Request $request, string $planId): JsonResponse
    {
        $plan = MealPlan::where('id', $planId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $plan) {
            return response()->json(['message' => 'Meal plan not found.'], 404);
        }

        return (new MealPlanResource($plan->load('user')))->response();
    }

    public function index(Request $request): JsonResponse
    {
        $plans = MealPlan::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return MealPlanResource::collection($plans->load('user'))
            ->response();
    }

    public function destroy(Request $request, string $planId): JsonResponse
    {
        $plan = MealPlan::where('id', $planId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $plan) {
            return response()->json(['message' => 'Meal plan not found.'], 404);
        }

        $plan->delete();

        return response()->json(['message' => 'Meal plan deleted.']);
    }

    public function regenerateDay(Request $request, string $planId, int $dayIndex): JsonResponse
    {
        $plan = MealPlan::where('id', $planId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $plan) {
            return response()->json(['message' => 'Meal plan not found.'], 404);
        }

        $newDay = $this->mealPlanService->regenerateDay($plan, $dayIndex);

        return response()->json($newDay);
    }

    /**
     * Premium: edit a meal plan's day/meal data directly.
     * Allows changing meals, costs, ingredients. Recalculates totals.
     */
    public function updateDay(Request $request, string $planId, int $dayIndex): JsonResponse
    {
        $user = $request->user();

        // Premium only
        if (! $user->subscription?->isActive()) {
            return response()->json(['message' => 'Subscription required to edit meal plans.'], 403);
        }

        $plan = MealPlan::where('id', $planId)
            ->where('user_id', $user->id)
            ->first();

        if (! $plan) {
            return response()->json(['message' => 'Meal plan not found.'], 404);
        }

        $days = $plan->days_json;

        if ($dayIndex < 0 || $dayIndex >= count($days)) {
            return response()->json(['message' => 'Day index out of range.'], 422);
        }

        $request->validate([
            'meals' => ['required', 'array', 'min:1'],
            'meals.*.type' => ['required', 'string', 'in:breakfast,lunch,dinner,meryenda'],
            'meals.*.name' => ['required', 'string'],
            'meals.*.description' => ['nullable', 'string'],
            'meals.*.ingredients' => ['nullable', 'array'],
            'meals.*.estimatedCost' => ['required', 'numeric', 'min:0'],
            'meals.*.isSkipped' => ['nullable', 'boolean'],
            'meals.*.isBasicMeal' => ['nullable', 'boolean'],
        ]);

        // Update the day
        $meals = $request->meals;
        $dailyCost = collect($meals)->where('isSkipped', '!==', true)->sum('estimatedCost');

        $days[$dayIndex]['meals'] = $meals;
        $days[$dayIndex]['dailyCost'] = $dailyCost;

        // Recalculate totals
        $totalCost = collect($days)->sum('dailyCost');
        $totalBudget = $plan->request_json['totalBudget'] ?? 0;
        $remainingBudget = $totalBudget - $totalCost;

        $plan->update([
            'days_json' => $days,
            'total_cost' => $totalCost,
            'remaining_budget' => $remainingBudget, // Can be negative — app shows warning
        ]);

        $response = [
            'dayIndex' => $dayIndex,
            'date' => $days[$dayIndex]['date'] ?? null,
            'meals' => $meals,
            'dailyCost' => $dailyCost,
            'totalCost' => $totalCost,
            'remainingBudget' => $remainingBudget,
            'overBudget' => $remainingBudget < 0,
        ];

        if ($remainingBudget < 0) {
            $response['warning'] = 'Total cost exceeds budget by ' . abs($remainingBudget) . ' ' . ($plan->request_json['currencyCode'] ?? '');
        }

        return response()->json($response);
    }
}
