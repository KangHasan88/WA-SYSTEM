<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tenant_mappings')) {
            Schema::create('tenant_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('central_tenant_id')->nullable()->unique();
                $table->string('name_snapshot');
                $table->string('slug_snapshot')->unique();
                $table->string('status')->default('active');
                $table->string('plan_snapshot')->nullable();
                $table->string('timezone')->default('Asia/Jakarta');
                $table->json('settings')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('tenant_users')) {
            Schema::create('tenant_users', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenant_mappings')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('central_tenant_user_id')->nullable()->index();
                $table->string('role')->default('operator');
                $table->boolean('is_default')->default(false);
                $table->timestamps();

                $table->unique(['tenant_id', 'user_id']);
            });
        }

        foreach ($this->tenantScopedTables() as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('tenant_mappings')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tenantScopedTables()) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tenant_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tenant_id');
                });
            }
        }

        Schema::dropIfExists('tenant_users');
        Schema::dropIfExists('tenant_mappings');
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
};
