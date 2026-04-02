<?php

namespace App\Providers;

use App\Services\ImageService;
use App\Services\MealPlanService;
use App\Services\OpenAIService;
use App\Services\SubscriptionService;
use App\Services\TierService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TierService::class);
        $this->app->singleton(OpenAIService::class);
        $this->app->singleton(SubscriptionService::class);
        $this->app->singleton(ImageService::class);
        $this->app->singleton(MealPlanService::class);
    }

    public function boot(): void
    {
        RateLimiter::for('meal-plan-generate', function (Request $request) {
            $user = $request->user();
            $subscription = $user ? \App\Models\Subscription::where('user_id', $user->id)->first() : null;
            $isPremium = $subscription && $subscription->isActive();
            $limit = $isPremium
                ? config('budgetbite.rate_limits.premium_plans_per_day', 10)
                : config('budgetbite.rate_limits.free_plans_per_day', 5);

            return Limit::perDay($limit)
                ->by($user?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) use ($limit, $isPremium) {
                    $tier = $isPremium ? 'Premium' : 'Free';
                    $upgrade = $isPremium ? '' : ' Upgrade to premium for more.';

                    return response()->json([
                        'message' => "{$tier} limit reached: {$limit} meal plans per day.{$upgrade}",
                        'limit' => $limit,
                        'tier' => $isPremium ? 'premium' : 'free',
                        'retryAfter' => $headers['Retry-After'] ?? null,
                    ], 429, $headers);
                });
        });

        RateLimiter::for('meal-plan-regenerate', function (Request $request) {
            $user = $request->user();
            $subscription = $user ? \App\Models\Subscription::where('user_id', $user->id)->first() : null;
            $isPremium = $subscription && $subscription->isActive();
            $limit = $isPremium ? 100 : config('budgetbite.rate_limits.free_regenerations_per_day', 5);

            return Limit::perDay($limit)
                ->by($user?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) use ($limit, $isPremium) {
                    $tier = $isPremium ? 'Premium' : 'Free';
                    $upgrade = $isPremium ? '' : ' Upgrade to premium for unlimited regenerations.';

                    return response()->json([
                        'message' => "{$tier} limit reached: {$limit} day regenerations per day.{$upgrade}",
                        'limit' => $limit,
                        'tier' => $isPremium ? 'premium' : 'free',
                        'retryAfter' => $headers['Retry-After'] ?? null,
                    ], 429, $headers);
                });
        });

        RateLimiter::for('api', function (Request $request) {
            $config = config('budgetbite.rate_limits.api_general');

            return Limit::perMinutes($config['decay_minutes'], $config['max_attempts'])
                ->by($request->user()?->id ?: $request->ip());
        });
    }
}
