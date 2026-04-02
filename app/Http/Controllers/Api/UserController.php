<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\AuthUserResource;
use App\Jobs\PopulateCountryFoodPrices;
use App\Models\Country;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::updateOrCreate(
            ['firebase_uid' => $request->firebase_uid],
            [
                'email' => $request->email,
                'display_name' => $request->display_name,
                'photo_url' => $request->photo_url,
                'is_anonymous' => $request->boolean('is_anonymous', false),
                'country' => $request->country_code,
                'name' => $request->display_name ?? $request->email ?? 'Guest',
            ],
        );

        // If user has a country, check if food prices need populating
        if ($request->country_code) {
            $code = strtoupper($request->country_code);
            $country = Country::find($code);

            if ($country && ! $country->prices_populated) {
                // Cache lock prevents duplicate jobs from concurrent registrations
                $lockKey = "foodprices:populating:{$code}";
                if (! cache()->has($lockKey)) {
                    cache()->put($lockKey, true, 300); // 5 min lock
                    PopulateCountryFoodPrices::dispatch($code);
                }
            }
        }

        return (new AuthUserResource($user))
            ->response()
            ->setStatusCode(200);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete all related data
        $user->mealPlans()->delete();
        $user->subscription()->delete();
        $user->fcmTokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account and all data deleted.']);
    }
}
