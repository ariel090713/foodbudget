<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'userId' => $this->user?->firebase_uid,
            'status' => $this->status,
            'request' => $this->request_json,
            'days' => $this->days_json,
            'generatedDays' => count($this->days_json ?? []),
            'totalDays' => $this->request_json['numberOfDays'] ?? 0,
            'totalCost' => (float) $this->total_cost,
            'remainingBudget' => (float) $this->remaining_budget,
            'detectedTier' => $this->detected_tier,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
