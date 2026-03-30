<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'userId' => $this->user?->firebase_uid,
            'status' => $this->status,
            'productId' => $this->product_id,
            'platform' => $this->platform,
            'expiresAt' => $this->expires_at?->toIso8601String(),
            'purchasedAt' => $this->purchased_at?->toIso8601String(),
        ];
    }
}
