<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ApiClientAuth;
use App\Http\Controllers\Api\WaGatewayApprovalController;
use App\Http\Controllers\Api\WaGatewayMessageController;
use App\Http\Controllers\Api\WaGatewayStatusController;
use App\Http\Controllers\WaInboxController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// ============================================
// WA WEBHOOK ROUTES (No CSRF)
// ============================================
Route::post('/wa-webhook/incoming', [WaInboxController::class, 'webhook'])->name('webhook.incoming');

// ============================================
// WA GATEWAY API V1
// ============================================
Route::prefix('v1')
    ->group(function () {
        Route::get('/auth-check', function (Request $request) {
            $client = $request->attributes->get('api_client');

            return response()->json([
                'success' => true,
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'slug' => $client->slug,
                    'scopes' => $client->scopes,
                    'rate_limit_per_minute' => $client->rate_limit_per_minute,
                    'last_used_at' => optional($client->last_used_at)->toISOString(),
                ],
            ]);
        })->middleware(ApiClientAuth::class . ':read:status')->name('api.v1.auth-check');

        Route::post('/messages/send', [WaGatewayMessageController::class, 'send'])
            ->middleware(ApiClientAuth::class . ':send:message')
            ->name('api.v1.messages.send');

        Route::get('/status', [WaGatewayStatusController::class, 'show'])
            ->middleware(ApiClientAuth::class . ':read:status')
            ->name('api.v1.status');

        Route::post('/approvals/request', [WaGatewayApprovalController::class, 'requestApproval'])
            ->middleware(ApiClientAuth::class . ':approval:request')
            ->name('api.v1.approvals.request');

        Route::get('/approvals/{approvalId}', [WaGatewayApprovalController::class, 'show'])
            ->middleware(ApiClientAuth::class . ':approval:read')
            ->name('api.v1.approvals.show');
    });

// ============================================
// Default API route
// ============================================
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
