<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CountryResource;
use App\Models\Country;
use App\Models\FoodPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    public function index(): JsonResponse
    {
        $countries = Country::orderBy('name')->get();

        return CountryResource::collection($countries)->response();
    }

    public function show(string $code): JsonResponse
    {
        $country = Country::find(strtoupper($code));

        if (! $country) {
            return response()->json(['message' => 'Country not found.'], 404);
        }

        return (new CountryResource($country))->response();
    }

    public function prices(string $code): JsonResponse
    {
        $country = Country::find(strtoupper($code));

        if (! $country) {
            return response()->json(['message' => 'Country not found.'], 404);
        }

        $prices = FoodPrice::where('country_code', $country->code)
            ->orderBy('category')
            ->orderByDesc('is_common')
            ->orderBy('item_name')
            ->get()
            ->map(fn ($p) => [
                'itemName' => $p->item_name,
                'localName' => $p->local_name,
                'unit' => $p->unit,
                'priceMin' => (float) $p->price_min,
                'priceMax' => (float) $p->price_max,
                'currencyCode' => $p->currency_code,
                'category' => $p->category,
                'isCommon' => $p->is_common,
            ]);

        return response()->json([
            'country' => new CountryResource($country),
            'prices' => $prices,
        ]);
    }
}
