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
}
