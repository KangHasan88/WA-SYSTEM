<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_inbox', function (Blueprint $table) {
            if (! Schema::hasColumn('wa_inbox', 'webhook_fingerprint')) {
                $table->string('webhook_fingerprint', 64)->nullable()->after('message_id')->unique();
            }

            if (! Schema::hasColumn('wa_inbox', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('caption');
            }

            if (! Schema::hasColumn('wa_inbox', 'approval_result')) {
                $table->json('approval_result')->nullable()->after('raw_payload');
            }

            if (! Schema::hasColumn('wa_inbox', 'ignored_reason')) {
                $table->string('ignored_reason')->nullable()->after('approval_result')->index();
            }

            if (! Schema::hasColumn('wa_inbox', 'webhook_source_ip')) {
                $table->string('webhook_source_ip', 45)->nullable()->after('ignored_reason');
            }

            if (! Schema::hasColumn('wa_inbox', 'webhook_user_agent')) {
                $table->string('webhook_user_agent', 255)->nullable()->after('webhook_source_ip');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wa_inbox', function (Blueprint $table) {
            foreach (['webhook_fingerprint', 'raw_payload', 'approval_result', 'ignored_reason', 'webhook_source_ip', 'webhook_user_agent'] as $column) {
                if (Schema::hasColumn('wa_inbox', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
