<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodPrice extends Model
{
    protected $fillable = [
        'country_code',
        'item_name',
        'local_name',
        'unit',
        'price_min',
        'price_max',
        'currency_code',
        'category',
        'is_common',
        'season',
        'availability',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'price_min' => 'decimal:2',
            'price_max' => 'decimal:2',
            'is_common' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }
}
