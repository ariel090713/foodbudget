<?php

use App\Http\Controllers\Api\CountryController;
use App\Http\Controllers\Api\FcmTokenController;
use App\Http\Controllers\Api\MealPlanController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

// Public — no auth
Route::post('webhooks/google-play', [WebhookController::class, 'googlePlay']);
Route::post('webhooks/app-store', [WebhookController::class, 'appStore']);

// Countries — API key only (no user needed, app uses this for country picker)
Route::middleware('api.key.only')->group(function () {
    Route::post('auth/register', [UserController::class, 'register']);
    Route::get('countries', [CountryController::class, 'index']);
    Route::get('countries/{code}', [CountryController::class, 'show']);
    Route::get('countries/{code}/prices', [CountryController::class, 'prices']);
});

// Authenticated — API key + user lookup
Route::middleware('api.key')->group(function () {
    // Meal Plans
    Route::get('meal-plans', [MealPlanController::class, 'index']);
    Route::post('meal-plans', [MealPlanController::class, 'store'])
        ->middleware('throttle:meal-plan-generate');
    Route::get('meal-plans/{planId}', [MealPlanController::class, 'show']);
    Route::delete('meal-plans/{planId}', [MealPlanController::class, 'destroy']);
    Route::post('meal-plans/{planId}/days/{dayIndex}/regenerate', [MealPlanController::class, 'regenerateDay'])
        ->middleware('throttle:meal-plan-regenerate');

    // Subscriptions
    Route::post('subscriptions/verify', [SubscriptionController::class, 'verify']);
    Route::get('subscriptions/status', [SubscriptionController::class, 'status']);
    Route::post('subscriptions/restore', [SubscriptionController::class, 'restore']);
    Route::post('subscriptions/redeem', [PromoCodeController::class, 'redeem']);

    // FCM Tokens
    Route::post('fcm-tokens', [FcmTokenController::class, 'store']);
    Route::delete('fcm-tokens', [FcmTokenController::class, 'destroy']);
});
