<?php

namespace App\Providers;

use App\Services\MealPlanService;
use App\Services\OpenAIService;
use App\Services\SubscriptionService;
use App\Services\TierService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TierService::class);
        $this->app->singleton(OpenAIService::class);
        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(MealPlanService::class);
    }

    public function boot(): void
    {
        // Meal plan generation: free = 3/day, premium = 10/day
        RateLimiter::for('meal-plan-generate', function (Request $request) {
            $user = $request->user();
            $isPremium = $user?->subscription?->isActive();
            $limit = $isPremium
                ? config('budgetbite.rate_limits.premium_plans_per_day', 10)
                : config('budgetbite.rate_limits.free_plans_per_day', 3);

            return Limit::perDay($limit)->by($user?->id ?: $request->ip());
        });

        // Day regeneration: free = 2/day, premium = unlimited (100/day)
        RateLimiter::for('meal-plan-regenerate', function (Request $request) {
            $user = $request->user();
            $isPremium = $user?->subscription?->isActive();
            $limit = $isPremium ? 100 : config('budgetbite.rate_limits.free_regenerations_per_day', 2);

            return Limit::perDay($limit)->by($user?->id ?: $request->ip());
        });

        // General API rate limit
        RateLimiter::for('api', function (Request $request) {
            $config = config('budgetbite.rate_limits.api_general');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
