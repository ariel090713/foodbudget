<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SubscriptionService
{
    public function verifyReceipt(string $receipt, string $platform, User $user): Subscription
    {
        // TODO: Implement actual store API verification
        // For now, stub that creates/updates the subscription record
        // In production, call Google Play Developer API or Apple App Store Server API

        $isValid = $this->verifyWithStore($receipt, $platform);

        if (! $isValid) {
            return $this->getOrCreateSubscription($user, ['status' => 'none']);
        }

        return $this->getOrCreateSubscription($user, [
            'product_id' => 'premium_monthly',
            'platform' => $platform,
            'status' => 'active',
            'receipt' => $receipt,
            'expires_at' => now()->addMonth(),
            'purchased_at' => now(),
        ]);
    }

    public function restorePurchase(string $receipt, string $platform, User $user): Subscription
    {
        return $this->verifyReceipt($receipt, $platform, $user);
    }

    public function getStatus(User $user): Subscription
    {
        $subscription = $user->subscription;

        if (! $subscription) {
            return $this->getOrCreateSubscription($user, ['status' => 'none']);
        }

        // Check if expired
        if ($subscription->status === 'active' && $subscription->expires_at?->isPast()) {
            $subscription->update(['status' => 'expired']);
        }

        return $subscription->fresh();
    }

    public function processWebhookEvent(string $platform, array $payload): void
    {
        // Extract user identifier and event type from payload
        // This is platform-specific and needs real implementation
        $eventType = $payload['event_type'] ?? null;
        $firebaseUid = $payload['firebase_uid'] ?? null;

        if (! $firebaseUid || ! $eventType) {
            Log::warning('Webhook missing required fields', ['platform' => $platform]);
            return;
        }

        $user = User::where('firebase_uid', $firebaseUid)->first();
        if (! $user) {
            Log::warning('Webhook user not found', ['firebase_uid' => $firebaseUid]);
            return;
        }

        $subscription = $user->subscription;
        if (! $subscription) {
            return;
        }

        match ($eventType) {
            'RENEWAL', 'DID_RENEW' => $subscription->update([
                'status' => 'active',
                'expires_at' => now()->addMonth(),
            ]),
            'CANCELLATION', 'DID_FAIL_TO_RENEW' => $subscription->update([
                'status' => 'cancelled',
            ]),
            'EXPIRATION', 'EXPIRED' => $subscription->update([
                'status' => 'expired',
            ]),
            default => Log::info('Unhandled webhook event', ['type' => $eventType]),
        };

        Log::info('Webhook processed', [
            'platform' => $platform,
            'event_type' => $eventType,
            'result' => $subscription->fresh()->status,
        ]);
    }

    public function isActive(User $user): bool
    {
        $subscription = $user->subscription;

        return $subscription && $subscription->isActive();
    }

    private function getOrCreateSubscription(User $user, array $attributes): Subscription
    {
        return Subscription::updateOrCreate(
            ['user_id' => $user->id],
            $attributes,
        );
    }

    private function verifyWithStore(string $receipt, string $platform): bool
    {
        // TODO: Implement real store verification
        // Google Play: use Google Play Developer API
        // App Store: use App Store Server API
        // For now, return false (treat as unverified)
        Log::info('Store verification stub called', ['platform' => $platform]);

        return false;
    }
}
