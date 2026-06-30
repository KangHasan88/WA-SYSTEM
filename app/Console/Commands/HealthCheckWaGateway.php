<?php

namespace App\Console\Commands;

use App\Services\WaRateLimitService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthCheckWaGateway extends Command
{
    protected $signature = 'wa:health-check {--json : Output JSON} {--no-alert : Jangan kirim alert WhatsApp}';

    protected $description = 'Cek kesehatan WA Gateway, queue, failed jobs, dan kirim alert jika ada gangguan.';

    public function handle(): int
    {
        $nodeHealth = $this->nodeJson('/health');
        $waStatus = $this->nodeJson('/wa-status');
        $pendingJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        $queueThreshold = (int) env('WA_ALERT_QUEUE_PENDING_THRESHOLD', 20);
        $failedThreshold = (int) env('WA_ALERT_FAILED_JOBS_THRESHOLD', 1);
        $ownerNumber = trim((string) env('WA_ALERT_OWNER_NUMBER', ''));

        $alerts = [];

        if (!$nodeHealth['ok']) {
            $alerts[] = 'Node sender tidak sehat atau tidak bisa diakses di 127.0.0.1:7070.';
        }

        if (!data_get($waStatus, 'data.connected', false)) {
            $alerts[] = 'WhatsApp disconnected. Perlu cek QR/session WA.';
        }

        if ($pendingJobs > $queueThreshold) {
            $alerts[] = "Queue pending tinggi: {$pendingJobs} jobs. Threshold: {$queueThreshold}.";
        }

        if ($failedJobs >= $failedThreshold) {
            $alerts[] = "Failed jobs terdeteksi: {$failedJobs}. Threshold: {$failedThreshold}.";
        }

        $payload = [
            'ok' => count($alerts) === 0,
            'timestamp' => now('Asia/Jakarta')->toDateTimeString(),
            'node' => [
                'running' => $nodeHealth['ok'],
                'status' => $nodeHealth['status'],
            ],
            'whatsapp' => [
                'connected' => (bool) data_get($waStatus, 'data.connected', false),
                'status' => data_get($waStatus, 'data.status', 'unknown'),
                'qr_available' => !empty(data_get($waStatus, 'data.qr')),
            ],
            'queue' => [
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
                'pending_threshold' => $queueThreshold,
                'failed_threshold' => $failedThreshold,
            ],
            'alerts' => $alerts,
            'alert_owner_configured' => $ownerNumber !== '',
        ];

        if (!$this->option('no-alert') && $alerts && $ownerNumber !== '') {
            $payload['alert_dispatched'] = $this->dispatchAlert($ownerNumber, $alerts, $payload);
        } else {
            $payload['alert_dispatched'] = false;
        }

        Log::info('WA Gateway health check', $payload);

        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } elseif ($payload['ok']) {
            $this->info('WA Gateway sehat.');
        } else {
            $this->warn('WA Gateway punya alert:');
            foreach ($alerts as $alert) {
                $this->line('- ' . $alert);
            }
        }

        return self::SUCCESS;
    }

    private function nodeJson(string $path): array
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
                'data' => ['error' => $e->getMessage()],
            ];
        }
    }

    private function dispatchAlert(string $ownerNumber, array $alerts, array $payload): bool
    {
        $cooldownMinutes = (int) env('WA_ALERT_COOLDOWN_MINUTES', 30);
        $fingerprint = sha1(implode('|', $alerts));
        $cacheKey = 'wa_gateway_alert_sent:' . $fingerprint;

        if (Cache::has($cacheKey)) {
            return false;
        }

        $message = "Alert WA Gateway\n\n"
            . "Waktu: {$payload['timestamp']} WIB\n"
            . "Status WA: " . ($payload['whatsapp']['connected'] ? 'connected' : 'disconnected') . "\n"
            . "Queue pending: {$payload['queue']['pending_jobs']}\n"
            . "Failed jobs: {$payload['queue']['failed_jobs']}\n\n"
            . "Masalah:\n- " . implode("\n- ", $alerts) . "\n\n"
            . "Saran: cek /delivery-reports, PM2 wa-blast/wa-queue, dan scan ulang WA jika disconnected.";

        app(WaRateLimitService::class)->dispatchMessage(
            $ownerNumber,
            $message,
            null,
            'Alert WA Gateway'
        );

        Cache::put($cacheKey, true, now()->addMinutes($cooldownMinutes));

        return true;
    }
}
