<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

class SmokeWaDeployment extends Command
{
    protected $signature = 'wa:smoke-deploy {--json : Output JSON}';

    protected $description = 'Run safe WA System smoke tests without sending live WhatsApp messages.';

    public function handle(): int
    {
        Queue::fake();

        $checks = [];
        $details = [];

        $this->runTransactionalSmoke($checks, $details);
        $this->runHealthSmoke($checks, $details);
        $this->runWebhookSmoke($checks, $details);

        $result = [
            'ok' => !in_array(false, $checks, true),
            'timestamp' => now('Asia/Jakarta')->toDateTimeString(),
            'safe_mode' => [
                'queue_fake_enabled' => true,
                'database_smoke_rolled_back' => true,
                'live_whatsapp_send' => false,
            ],
            'checks' => $checks,
            'details' => $details,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($checks as $name => $passed) {
                $this->line(($passed ? '[OK] ' : '[FAIL] ') . $name);
            }
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }

    private function runTransactionalSmoke(array &$checks, array &$details): void
    {
        DB::beginTransaction();

        try {
            $token = ApiClient::generatePlainToken();
            $client = ApiClient::create([
                'name' => 'Smoke Deploy Client',
                'slug' => 'smoke-deploy-' . Str::lower(Str::random(8)),
                'token_hash' => ApiClient::hashToken($token),
                'token_created_at' => now(),
                'scopes' => ['*'],
                'rate_limit_per_minute' => 60,
                'is_active' => true,
            ]);

            $auth = $this->apiJson('GET', '/api/v1/auth-check', $token);
            $status = $this->apiJson('GET', '/api/v1/status', $token);
            $message = $this->apiJson('POST', '/api/v1/messages/send', $token, [
                'to' => '081200000000',
                'message' => 'Smoke deploy dummy - tidak dikirim live',
                'source' => 'smoke-deploy',
                'reference_type' => 'smoke',
                'reference_id' => 'deploy-' . now()->format('YmdHis'),
            ]);
            $approval = $this->apiJson('POST', '/api/v1/approvals/request', $token, [
                'to' => '081200000000',
                'source' => 'smoke-deploy',
                'action' => 'Smoke deploy approval dry-run',
                'risk' => 'low',
                'dry_run' => true,
                'payload' => ['smoke' => true],
            ]);
            $approvalId = data_get($approval, 'json.approval_id');
            $approvalStatus = $approvalId
                ? $this->apiJson('GET', '/api/v1/approvals/' . $approvalId, $token)
                : ['status' => null, 'json' => []];

            $checks['auth_check_status_200'] = $auth['status'] === 200 && data_get($auth, 'json.success') === true;
            $checks['api_status_status_200'] = $status['status'] === 200 && data_get($status, 'json.success') === true;
            $checks['send_dummy_queued_without_live_send'] = $message['status'] === 202
                && data_get($message, 'json.status') === 'queued';
            $checks['approval_request_dry_run_accepted'] = $approval['status'] === 202
                && data_get($approval, 'json.dry_run') === true
                && data_get($approval, 'json.sent_at') === null;
            $checks['approval_polling_status_200'] = $approvalStatus['status'] === 200
                && data_get($approvalStatus, 'json.status') === 'pending';

            $details['transactional_api'] = [
                'client_id' => $client->id,
                'auth_status' => $auth['status'],
                'status_status' => $status['status'],
                'message_status' => $message['status'],
                'approval_status' => $approval['status'],
                'approval_poll_status' => $approvalStatus['status'],
            ];
        } catch (\Throwable $e) {
            $checks['transactional_api_exception'] = false;
            $details['transactional_api_exception'] = $e->getMessage();
        } finally {
            DB::rollBack();
        }
    }

    private function runHealthSmoke(array &$checks, array &$details): void
    {
        $exitCode = Artisan::call('wa:health-check', [
            '--json' => true,
            '--no-alert' => true,
        ]);

        $output = trim(Artisan::output());
        $payload = json_decode($output, true) ?: [];

        $checks['health_check_command_runs'] = $exitCode === self::SUCCESS && is_array($payload);
        $checks['queue_health_available'] = array_key_exists('queue', $payload);
        $checks['node_health_checked'] = array_key_exists('node', $payload);

        $details['health_check'] = [
            'exit_code' => $exitCode,
            'ok' => data_get($payload, 'ok'),
            'node_running' => data_get($payload, 'node.running'),
            'whatsapp_connected' => data_get($payload, 'whatsapp.connected'),
            'pending_jobs' => data_get($payload, 'queue.pending_jobs'),
            'failed_jobs' => data_get($payload, 'queue.failed_jobs'),
        ];
    }

    private function runWebhookSmoke(array &$checks, array &$details): void
    {
        $exitCode = Artisan::call('wa:webhook-smoke', ['--json' => true]);
        $output = trim(Artisan::output());
        $payload = json_decode($output, true) ?: [];

        $checks['approval_webhook_parser_smoke'] = $exitCode === self::SUCCESS
            && data_get($payload, 'ok') === true;

        $details['webhook_smoke'] = [
            'exit_code' => $exitCode,
            'checks' => data_get($payload, 'checks', []),
        ];
    }

    private function apiJson(string $method, string $uri, string $token, array $payload = []): array
    {
        $server = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
            'REMOTE_ADDR' => '127.0.0.1',
        ];

        $request = Request::create($uri, $method, $payload, [], [], $server);
        $response = app(HttpKernel::class)->handle($request);

        return [
            'status' => $response->getStatusCode(),
            'json' => json_decode($response->getContent(), true) ?: [],
        ];
    }
}
