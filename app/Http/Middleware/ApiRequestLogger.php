<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiRequestLogger
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        Log::info('API Request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => $request->user()?->id,
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
