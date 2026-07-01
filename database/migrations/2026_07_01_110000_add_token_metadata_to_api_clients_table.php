<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            if (! Schema::hasColumn('api_clients', 'token_created_at')) {
                $table->timestamp('token_created_at')->nullable()->after('token_hash');
            }

            if (! Schema::hasColumn('api_clients', 'token_rotated_at')) {
                $table->timestamp('token_rotated_at')->nullable()->after('token_created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_clients', function (Blueprint $table) {
            foreach (['token_created_at', 'token_rotated_at'] as $column) {
                if (Schema::hasColumn('api_clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
