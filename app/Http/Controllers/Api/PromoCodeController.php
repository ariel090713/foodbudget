<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionInfoResource;
use App\Models\PromoCode;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $code = strtoupper(trim($request->code));
        $user = $request->user();

        $promo = PromoCode::where('code', $code)->first();

        if (! $promo) {
            return response()->json(['message' => 'Invalid promo code.'], 422);
        }

        if (! $promo->isValid()) {
            return response()->json(['message' => 'This promo code has expired or reached its usage limit.'], 422);
        }

        if ($promo->wasRedeemedBy($user)) {
            return response()->json(['message' => 'You have already redeemed this promo code.'], 422);
        }

        // Activate or extend subscription
        $subscription = Subscription::firstOrNew(['user_id' => $user->id]);

        $now = now();
        $currentExpiry = ($subscription->exists && $subscription->isActive())
            ? $subscription->expires_at
            : $now;

        $subscription->fill([
            'status' => 'active',
            'product_id' => 'promo_' . strtolower($promo->code),
            'platform' => 'promo',
            'purchased_at' => $subscription->purchased_at ?? $now,
            'expires_at' => $currentExpiry->copy()->addDays($promo->duration_days),
        ]);
        $subscription->save();

        // Record redemption
        $promo->users()->attach($user->id);
        $promo->increment('times_used');

        return response()->json([
            'message' => "Promo code redeemed! You have {$promo->duration_days} days of premium.",
            'subscription' => new SubscriptionInfoResource($subscription->load('user')),
        ]);
    }
}
