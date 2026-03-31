<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'flag_emoji',
        'currency_code',
        'currency_symbol',
        'currency_name',
        'tier_poor_min',
        'tier_middle_class_min',
        'tier_rich_min',
        'prices_populated',
        'prices_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'tier_poor_min' => 'decimal:2',
            'tier_middle_class_min' => 'decimal:2',
            'tier_rich_min' => 'decimal:2',
            'prices_populated' => 'boolean',
            'prices_updated_at' => 'datetime',
        ];
    }

    public function foodPrices(): HasMany
    {
        return $this->hasMany(FoodPrice::class, 'country_code', 'code');
    }
}
