<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class WaGatewayStatusController extends Controller
{
    public function show()
    {
        $start = microtime(true);

        $nodeHealth = $this->getNodeJson('/health');
        $waStatus = $this->getNodeJson('/wa-status');

        $response = [
            'success' => true,
            'service' => 'kurmigo-wa-gateway',
            'timestamp' => now()->toISOString(),
            'laravel' => [
                'app_env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'queue_connection' => config('queue.default'),
            ],
            'node' => [
                'running' => $nodeHealth['ok'],
                'health' => $nodeHealth['data'],
                'backend_url' => 'http://127.0.0.1:7070',
            ],
            'whatsapp' => [
                'connected' => (bool) data_get($waStatus, 'data.connected', false),
                'status' => data_get($waStatus, 'data.status', 'unknown'),
                'qr_available' => !empty(data_get($waStatus, 'data.qr')),
                'raw' => $waStatus['data'],
            ],
            'queue' => [
                'pending_jobs' => DB::table('jobs')->count(),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
            'api_messages' => [
                'queued' => DB::table('api_message_requests')->where('status', 'queued')->count(),
                'sending' => DB::table('api_message_requests')->where('status', 'sending')->count(),
                'success' => DB::table('api_message_requests')->where('status', 'success')->count(),
                'failed' => DB::table('api_message_requests')->where('status', 'failed')->count(),
                'invalid' => DB::table('api_message_requests')->where('status', 'invalid')->count(),
            ],
        ];

        $response['response_time_ms'] = round((microtime(true) - $start) * 1000, 2);

        return response()->json($response);
    }

    private function getNodeJson(string $path): array
    {
        try {
            $response = Http::timeout(3)
                ->connectTimeout(1)
                ->get('http://127.0.0.1:7070' . $path);

            return [
                'ok' => $response->ok(),
                'status' => $response->status(),
                'data' => $response->json() ?: [],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'status' => null,
                'data' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }
}
