<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        if (! $apiKey || $apiKey !== config('budgetbite.api_key')) {
            return response()->json(['message' => 'Invalid API key.'], 401);
        }

        $firebaseUid = $request->header('X-Firebase-Uid');

        if (! $firebaseUid) {
            return response()->json(['message' => 'Missing X-Firebase-Uid header.'], 401);
        }

        $user = User::where('firebase_uid', $firebaseUid)->first();

        if (! $user) {
            return response()->json(['message' => 'User not registered. Call POST /api/auth/register first.'], 401);
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
