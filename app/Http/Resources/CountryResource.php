<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'flagEmoji' => $this->flag_emoji,
            'currencyCode' => $this->currency_code,
            'currencySymbol' => $this->currency_symbol,
            'currencyName' => $this->currency_name,
            'tierPoorMin' => (float) $this->tier_poor_min,
            'tierMiddleClassMin' => (float) $this->tier_middle_class_min,
            'tierRichMin' => (float) $this->tier_rich_min,
            'pricesPopulated' => $this->prices_populated,
        ];
    }
}
