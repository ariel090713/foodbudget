<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DestroyFcmTokenRequest;
use App\Http\Requests\Api\StoreFcmTokenRequest;
use App\Models\FcmToken;
use Illuminate\Http\JsonResponse;

class FcmTokenController extends Controller
{
    public function store(StoreFcmTokenRequest $request): JsonResponse
    {
        FcmToken::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'token' => $request->token,
            ],
            [
                'platform' => $request->platform,
            ],
        );

        return response()->json(['message' => 'Token registered.'], 201);
    }

    public function destroy(DestroyFcmTokenRequest $request): JsonResponse
    {
        FcmToken::where('user_id', $request->user()->id)
            ->where('token', $request->token)
            ->delete();

        return response()->json(['message' => 'Token removed.']);
    }
}
