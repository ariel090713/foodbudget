<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlan extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'request_json',
        'days_json',
        'total_cost',
        'remaining_budget',
        'detected_tier',
    ];

    protected function casts(): array
    {
        return [
            'request_json' => 'array',
            'days_json' => 'array',
            'total_cost' => 'decimal:2',
            'remaining_budget' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
