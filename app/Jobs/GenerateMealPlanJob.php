<?php

namespace App\Jobs;

use App\Models\MealPlan;
use App\Models\User;
use App\Services\MealPlanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMealPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 600; // 10 minutes for large plans

    public function __construct(
        public string $mealPlanId,
        public array $params,
        public int $userId,
    ) {}

    public function handle(MealPlanService $service): void
    {
        $plan = MealPlan::find($this->mealPlanId);
        if (! $plan || $plan->status !== 'generating') {
            return;
        }

        $user = User::find($this->userId);
        if (! $user) {
            $plan->update(['status' => 'failed']);
            return;
        }

        try {
            $service->processGenerationChunked($plan, $this->params, $user);
        } catch (\Throwable $e) {
            Log::error('Meal plan job failed', [
                'plan_id' => $this->mealPlanId,
                'error' => $e->getMessage(),
            ]);
            $plan->update(['status' => 'failed']);
        }
    }
}
