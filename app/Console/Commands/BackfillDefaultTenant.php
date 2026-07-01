<?php

namespace App\Console\Commands;

use App\Models\TenantMapping;
use App\Models\TenantUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillDefaultTenant extends Command
{
    protected $signature = 'wa:tenant-default-backfill
        {--central-tenant-id= : Tenant id dari central Usaha-Up}
        {--slug=demo-nusantara : Slug tenant dari central Usaha-Up}
        {--name=PT Demo Nusantara : Nama tenant dari central Usaha-Up}
        {--status=active : Status tenant}
        {--plan=internal : Snapshot plan tenant}
        {--json : Output JSON}';

    protected $description = 'Create default Usaha-Up tenant mapping and backfill existing WA System data.';

    public function handle(): int
    {
        $result = DB::transaction(function () {
            $tenant = $this->resolveTenantMapping();
            $tenantUser = $this->ensureDefaultTenantUser($tenant);
            $backfilled = [];

            foreach ($this->tenantScopedTables() as $tableName) {
                if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'tenant_id')) {
                    $backfilled[$tableName] = [
                        'available' => false,
                        'updated' => 0,
                        'remaining_null' => null,
                    ];
                    continue;
                }

                $updated = DB::table($tableName)
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $tenant->id]);

                $backfilled[$tableName] = [
                    'available' => true,
                    'updated' => $updated,
                    'remaining_null' => DB::table($tableName)->whereNull('tenant_id')->count(),
                    'total' => DB::table($tableName)->count(),
                ];
            }

            return [
                'ok' => !collect($backfilled)->contains(fn ($item) => ($item['remaining_null'] ?? 0) > 0),
                'tenant' => [
                    'id' => $tenant->id,
                    'central_tenant_id' => $tenant->central_tenant_id,
                    'slug_snapshot' => $tenant->slug_snapshot,
                    'name_snapshot' => $tenant->name_snapshot,
                    'status' => $tenant->status,
                ],
                'tenant_user' => $tenantUser ? [
                    'id' => $tenantUser->id,
                    'user_id' => $tenantUser->user_id,
                    'role' => $tenantUser->role,
                ] : null,
                'backfilled' => $backfilled,
            ];
        });

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->info('Default tenant mapping: ' . $result['tenant']['slug_snapshot']);
            foreach ($result['backfilled'] as $table => $data) {
                $this->line($table . ': updated=' . $data['updated'] . ', remaining_null=' . ($data['remaining_null'] ?? 'n/a'));
            }
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }

    private function resolveTenantMapping(): TenantMapping
    {
        $centralTenantId = trim((string) $this->option('central-tenant-id')) ?: null;
        $slug = trim((string) $this->option('slug')) ?: 'demo-nusantara';

        $query = TenantMapping::query();
        $tenant = $centralTenantId
            ? $query->where('central_tenant_id', $centralTenantId)->first()
            : $query->where('slug_snapshot', $slug)->first();

        if (!$tenant) {
            $tenant = new TenantMapping();
            $tenant->central_tenant_id = $centralTenantId;
            $tenant->slug_snapshot = $slug;
        }

        $tenant->fill([
            'central_tenant_id' => $centralTenantId ?: $tenant->central_tenant_id,
            'name_snapshot' => trim((string) $this->option('name')) ?: 'PT Demo Nusantara',
            'slug_snapshot' => $slug,
            'status' => trim((string) $this->option('status')) ?: 'active',
            'plan_snapshot' => trim((string) $this->option('plan')) ?: 'internal',
            'timezone' => 'Asia/Jakarta',
            'settings' => array_merge($tenant->settings ?: [], [
                'source' => 'usahaup',
                'default_mapping' => true,
            ]),
            'synced_at' => now(),
        ]);
        $tenant->save();

        return $tenant;
    }

    private function ensureDefaultTenantUser(TenantMapping $tenant): ?TenantUser
    {
        if (!Schema::hasTable('tenant_users') || !Schema::hasTable('users')) {
            return null;
        }

        $user = User::query()->orderBy('id')->first();
        if (!$user) {
            return null;
        }

        return TenantUser::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
            ],
            [
                'role' => 'owner',
                'is_default' => true,
            ]
        );
    }

    private function tenantScopedTables(): array
    {
        return [
            'contact_groups',
            'contacts',
            'wa_logs',
            'wa_schedules',
            'wa_inbox',
            'follow_up_reminders',
            'api_clients',
            'api_message_requests',
            'approval_requests',
        ];
    }
}
