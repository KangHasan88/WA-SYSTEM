<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wa_accounts')) {
            Schema::create('wa_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenant_mappings')->cascadeOnDelete();
                $table->string('label');
                $table->string('phone_number')->nullable();
                $table->string('session_id')->unique();
                $table->string('session_path')->nullable();
                $table->string('status')->default('unknown');
                $table->timestamp('last_connected_at')->nullable();
                $table->timestamp('last_disconnected_at')->nullable();
                $table->unsignedInteger('rate_limit_per_hour')->default(100);
                $table->boolean('is_default')->default(false);
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'is_default']);
            });
        }

        foreach ($this->waAccountScopedTables() as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'wa_account_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('wa_account_id')
                        ->nullable()
                        ->after('tenant_id')
                        ->constrained('wa_accounts')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->waAccountScopedTables()) as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'wa_account_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('wa_account_id');
                });
            }
        }

        Schema::dropIfExists('wa_accounts');
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
};
