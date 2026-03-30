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
        RateLimiter::for('meal-plan-generate', function (Request $request) {
            $config = config('budgetbite.rate_limits.meal_plan_generate');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('meal-plan-regenerate', function (Request $request) {
            $config = config('budgetbite.rate_limits.meal_plan_regenerate');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            $config = config('budgetbite.rate_limits.api_general');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
