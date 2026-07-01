<?php

namespace App\Console\Commands;

use App\Models\TenantMapping;
use App\Models\WaAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class BackfillDefaultWaAccount extends Command
{
    protected $signature = 'wa:account-default-backfill
        {--tenant-slug=demo-nusantara : Tenant mapping slug}
        {--label=Main WhatsApp : Label default WA account}
        {--session-path= : Existing session folder path}
        {--session-id= : Existing session id without session- prefix}
        {--json : Output JSON}';

    protected $description = 'Create default WA account mapping from existing connected session and backfill existing WA data.';

    public function handle(): int
    {
        $result = DB::transaction(function () {
            $tenant = TenantMapping::where('slug_snapshot', $this->option('tenant-slug'))->firstOrFail();
            $session = $this->resolveExistingSession();
            $status = $this->resolveNodeStatus();

            $account = WaAccount::updateOrCreate(
                ['session_id' => $session['session_id']],
                [
                    'tenant_id' => $tenant->id,
                    'label' => $this->option('label') ?: 'Main WhatsApp',
                    'phone_number' => null,
                    'session_path' => $session['session_path'],
                    'status' => $status['connected'] ? 'connected' : ($status['status'] ?: 'unknown'),
                    'last_connected_at' => $status['connected'] ? now() : null,
                    'rate_limit_per_hour' => 100,
                    'is_default' => true,
                    'is_active' => true,
                    'metadata' => [
                        'source' => 'existing_wwebjs_session',
                        'mapped_without_reconnect' => true,
                        'node_status' => $status,
                    ],
                ]
            );

            WaAccount::where('tenant_id', $tenant->id)
                ->where('id', '!=', $account->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);

            $backfilled = [];
            foreach ($this->waAccountScopedTables() as $tableName) {
                if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'wa_account_id')) {
                    $backfilled[$tableName] = [
                        'available' => false,
                        'updated' => 0,
                        'remaining_null' => null,
                    ];
                    continue;
                }

                $query = DB::table($tableName)->whereNull('wa_account_id');
                if (Schema::hasColumn($tableName, 'tenant_id')) {
                    $query->where('tenant_id', $tenant->id);
                }

                $updated = $query->update(['wa_account_id' => $account->id]);

                $remainingQuery = DB::table($tableName)->whereNull('wa_account_id');
                if (Schema::hasColumn($tableName, 'tenant_id')) {
                    $remainingQuery->where('tenant_id', $tenant->id);
                }

                $backfilled[$tableName] = [
                    'available' => true,
                    'updated' => $updated,
                    'remaining_null' => $remainingQuery->count(),
                    'total' => DB::table($tableName)
                        ->when(Schema::hasColumn($tableName, 'tenant_id'), fn ($query) => $query->where('tenant_id', $tenant->id))
                        ->count(),
                ];
            }

            return [
                'ok' => !collect($backfilled)->contains(fn ($item) => ($item['remaining_null'] ?? 0) > 0),
                'tenant' => [
                    'id' => $tenant->id,
                    'slug_snapshot' => $tenant->slug_snapshot,
                    'central_tenant_id' => $tenant->central_tenant_id,
                ],
                'wa_account' => [
                    'id' => $account->id,
                    'label' => $account->label,
                    'session_id' => $account->session_id,
                    'session_path' => $account->session_path,
                    'status' => $account->status,
                    'is_default' => $account->is_default,
                ],
                'backfilled' => $backfilled,
            ];
        });

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('Default WA account: ' . $result['wa_account']['session_id']);
            foreach ($result['backfilled'] as $table => $data) {
                $this->line($table . ': updated=' . $data['updated'] . ', remaining_null=' . ($data['remaining_null'] ?? 'n/a'));
            }
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }

    private function resolveExistingSession(): array
    {
        $explicitPath = trim((string) $this->option('session-path'));
        $explicitId = trim((string) $this->option('session-id'));

        if ($explicitPath !== '' && $explicitId !== '') {
            return [
                'session_path' => $explicitPath,
                'session_id' => $explicitId,
            ];
        }

        $basePath = base_path('wa-sender/.wwebjs_auth');
        $matches = glob($basePath . '/session-*') ?: [];

        if (!$matches) {
            throw new \RuntimeException('Tidak ada folder session WhatsApp existing di ' . $basePath);
        }

        usort($matches, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $sessionPath = $matches[0];
        $folder = basename($sessionPath);

        return [
            'session_path' => 'wa-sender/.wwebjs_auth/' . $folder,
            'session_id' => preg_replace('/^session-/', '', $folder),
        ];
    }

    private function resolveNodeStatus(): array
    {
        try {
            $response = Http::timeout(3)
                ->connectTimeout(1)
                ->get('http://127.0.0.1:7070/wa-status');

            $json = $response->json() ?: [];

            return [
                'reachable' => $response->ok(),
                'connected' => (bool) ($json['connected'] ?? false),
                'status' => $json['status'] ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'reachable' => false,
                'connected' => false,
                'status' => 'unreachable',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function waAccountScopedTables(): array
    {
        return [
            'wa_logs',
            'wa_schedules',
            'wa_inbox',
            'api_message_requests',
            'approval_requests',
        ];
    }
}
