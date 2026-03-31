<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

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
                'name' => $request->display_name ?? $request->email ?? 'Guest',
            ],
        );

        return (new AuthUserResource($user))
            ->response()
            ->setStatusCode(200);
    }
}
