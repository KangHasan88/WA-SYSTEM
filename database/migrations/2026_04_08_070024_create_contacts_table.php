<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('contact_groups')->onDelete('cascade');
            $table->string('number', 20);
            $table->string('name', 100)->nullable();
            $table->timestamps();
            
            $table->index('group_id');
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};