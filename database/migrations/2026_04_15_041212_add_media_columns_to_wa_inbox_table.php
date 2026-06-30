<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wa_inbox', function (Blueprint $table) {
            // Tambah kolom untuk media attachment (semua nullable agar tidak ganggu data lama)
            $table->string('media_mime')->nullable()->after('media_url');
            $table->integer('media_size')->nullable()->after('media_mime');
            $table->string('media_filename')->nullable()->after('media_size');
            $table->string('media_thumbnail')->nullable()->after('media_filename');
        });
    }

    public function down(): void
    {
        Schema::table('wa_inbox', function (Blueprint $table) {
            $table->dropColumn(['media_mime', 'media_size', 'media_filename', 'media_thumbnail']);
        });
    }
};