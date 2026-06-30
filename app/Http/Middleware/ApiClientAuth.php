<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ApiClientAuth
{
    public function handle(Request $request, Closure $next, ?string $scope = null)
    {
        $token = $request->bearerToken();
        $client = ApiClient::findActiveByToken($token);

        if (!$client) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized API client.',
            ], 401);
        }

        if ($scope && !$client->hasScope($scope)) {
            return response()->json([
                'success' => false,
                'message' => 'API client does not have the required scope.',
                'required_scope' => $scope,
            ], 403);
        }

        $windowKey = 'wa_api_client:' . $client->id . ':' . now()->format('YmdHi');
        $currentCount = Cache::increment($windowKey);
        if ($currentCount === 1) {
            Cache::put($windowKey, 1, now()->addMinutes(2));
        }

        if ($currentCount > $client->rate_limit_per_minute) {
            return response()->json([
                'success' => false,
                'message' => 'API client rate limit exceeded.',
                'limit_per_minute' => $client->rate_limit_per_minute,
            ], 429);
        }

        $client->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('api_client', $client);

        Log::info('WA Gateway API request', [
            'client_id' => $client->id,
            'client_slug' => $client->slug,
            'scope' => $scope,
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
        ]);

        return $next($request);
    }
}
