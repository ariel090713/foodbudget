<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    public function googlePlay(Request $request): JsonResponse
    {
        // TODO: Validate Google Play webhook signature
        // For now, process the payload directly
        Log::info('Google Play webhook received', ['payload' => $request->except('receipt')]);

        try {
            $this->subscriptionService->processWebhookEvent('android', $request->all());
        } catch (\Throwable $e) {
            Log::error('Google Play webhook processing failed', ['message' => $e->getMessage()]);

            return response()->json(['message' => 'Webhook processing failed.'], 400);
        }

        return response()->json(['message' => 'OK']);
    }

    public function appStore(Request $request): JsonResponse
    {
        // TODO: Validate App Store Server Notifications V2 payload
        Log::info('App Store webhook received', ['payload' => $request->except('receipt')]);

        try {
            $this->subscriptionService->processWebhookEvent('ios', $request->all());
        } catch (\Throwable $e) {
            Log::error('App Store webhook processing failed', ['message' => $e->getMessage()]);

            return response()->json(['message' => 'Webhook processing failed.'], 400);
        }

        return response()->json(['message' => 'OK']);
    }
}
