<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_inbox', function (Blueprint $table) {
            if (!Schema::hasColumn('wa_inbox', 'lead_status')) {
                $table->string('lead_status', 50)->default('new')->after('is_replied');
            }
            if (!Schema::hasColumn('wa_inbox', 'lead_notes')) {
                $table->text('lead_notes')->nullable()->after('lead_status');
            }
            if (!Schema::hasColumn('wa_inbox', 'assigned_to')) {
                $table->string('assigned_to', 100)->nullable()->after('lead_notes');
            }
            if (!Schema::hasColumn('wa_inbox', 'follow_up_date')) {
                $table->datetime('follow_up_date')->nullable()->after('assigned_to');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wa_inbox', function (Blueprint $table) {
            $table->dropColumn(['lead_status', 'lead_notes', 'assigned_to', 'follow_up_date']);
        });
    }
};