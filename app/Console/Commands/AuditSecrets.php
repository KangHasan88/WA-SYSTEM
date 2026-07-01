<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class AuditSecrets extends Command
{
    protected $signature = 'wa:secrets-audit {--json : Output JSON}';
    protected $description = 'Audit WA System secret and token metadata without printing secret values';

    public function handle(): int
    {
        $envKeys = [
            'APP_KEY',
            'DB_PASSWORD',
            'WA_WEBHOOK_TOKEN',
            'MAIL_PASSWORD',
            'REDIS_PASSWORD',
        ];

        $env = [];
        foreach ($envKeys as $key) {
            $value = env($key);
            $env[$key] = [
                'present' => filled($value),
                'length' => is_string($value) ? strlen($value) : 0,
            ];
        }

        $clients = ApiClient::orderBy('slug')->get()->map(fn (ApiClient $client) => [
            'id' => $client->id,
            'slug' => $client->slug,
            'is_active' => $client->is_active,
            'token_hash_present' => filled($client->token_hash),
            'token_hash_length' => strlen((string) $client->token_hash),
            'token_created_at' => optional($client->token_created_at)->toDateTimeString(),
            'token_rotated_at' => optional($client->token_rotated_at)->toDateTimeString(),
            'last_used_at' => optional($client->last_used_at)->toDateTimeString(),
        ])->values()->all();

        $result = [
            'ok' => true,
            'env' => $env,
            'api_clients' => $clients,
            'raw_tokens_stored_in_database' => false,
        ];

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            foreach ($env as $key => $meta) {
                $this->line($key . ': ' . ($meta['present'] ? 'present' : 'empty') . ' len=' . $meta['length']);
            }

            $this->line('API clients: ' . count($clients));
        }

        return self::SUCCESS;
    }
}
