<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\VerifySubscriptionRequest;
use App\Http\Resources\SubscriptionInfoResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    public function verify(VerifySubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->verifyReceipt(
            $request->receipt,
            $request->platform,
            $request->user(),
        );

        return (new SubscriptionInfoResource($subscription->load('user')))
            ->response();
    }

    public function status(Request $request): JsonResponse
    {
        $subscription = $this->subscriptionService->getStatus($request->user());

        return (new SubscriptionInfoResource($subscription->load('user')))
            ->response();
    }

    public function restore(VerifySubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->restorePurchase(
            $request->receipt,
            $request->platform,
            $request->user(),
        );

        return (new SubscriptionInfoResource($subscription->load('user')))
            ->response();
    }
}
