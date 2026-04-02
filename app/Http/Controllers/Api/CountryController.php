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

    public function prices(Request $request, string $code): JsonResponse
    {
        $country = Country::find(strtoupper($code));

        if (! $country) {
            return response()->json(['message' => 'Country not found.'], 404);
        }

        $query = FoodPrice::where('country_code', $country->code);

        // Filter by category
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        // Search by name
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                  ->orWhere('local_name', 'like', "%{$search}%");
            });
        }

        // Sort
        $sort = $request->query('sort', 'name'); // name, price_low, price_high
        match ($sort) {
            'price_low' => $query->orderBy('price_min'),
            'price_high' => $query->orderByDesc('price_max'),
            default => $query->orderBy('item_name'),
        };

        $perPage = min((int) $request->query('per_page', 50), 200);
        $paginated = $query->paginate($perPage);

        return response()->json([
            'country' => new CountryResource($country),
            'prices' => collect($paginated->items())->map(fn ($p) => [
                'id' => $p->id,
                'itemName' => $p->item_name,
                'localName' => $p->local_name,
                'unit' => $p->unit,
                'priceMin' => (float) $p->price_min,
                'priceMax' => (float) $p->price_max,
                'currencyCode' => $p->currency_code,
                'category' => $p->category,
                'isCommon' => $p->is_common,
            ]),
            'pagination' => [
                'total' => $paginated->total(),
                'perPage' => $paginated->perPage(),
                'currentPage' => $paginated->currentPage(),
                'lastPage' => $paginated->lastPage(),
            ],
        ]);
    }
}
