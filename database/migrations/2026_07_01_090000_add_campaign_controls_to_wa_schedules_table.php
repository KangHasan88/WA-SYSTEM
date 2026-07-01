<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE wa_schedules MODIFY status ENUM('pending','processing','paused','completed','cancelled','failed') NOT NULL DEFAULT 'pending'");

        Schema::table('wa_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('wa_schedules', 'campaign_plan')) {
                $table->json('campaign_plan')->nullable()->after('numbers');
            }

            if (! Schema::hasColumn('wa_schedules', 'next_number_index')) {
                $table->unsignedInteger('next_number_index')->default(0)->after('total_numbers');
            }

            if (! Schema::hasColumn('wa_schedules', 'dispatched_count')) {
                $table->unsignedInteger('dispatched_count')->default(0)->after('next_number_index');
            }

            if (! Schema::hasColumn('wa_schedules', 'next_dispatch_at')) {
                $table->dateTime('next_dispatch_at')->nullable()->after('scheduled_at')->index();
            }

            if (! Schema::hasColumn('wa_schedules', 'paused_at')) {
                $table->dateTime('paused_at')->nullable()->after('next_dispatch_at');
            }

            if (! Schema::hasColumn('wa_schedules', 'cancelled_at')) {
                $table->dateTime('cancelled_at')->nullable()->after('paused_at');
            }

            if (! Schema::hasColumn('wa_schedules', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wa_schedules', function (Blueprint $table) {
            foreach (['campaign_plan', 'next_number_index', 'dispatched_count', 'next_dispatch_at', 'paused_at', 'cancelled_at', 'completed_at'] as $column) {
                if (Schema::hasColumn('wa_schedules', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::statement("ALTER TABLE wa_schedules MODIFY status ENUM('pending','processing','completed','cancelled','failed') NOT NULL DEFAULT 'pending'");
    }
};
