<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wa_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100)->nullable();
            $table->text('message');
            $table->string('image_url')->nullable();
            $table->json('numbers');
            $table->integer('total_numbers');
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'failed'])->default('pending');
            $table->integer('sent_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamps();
            
            $table->index('scheduled_at');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wa_schedules');
    }
};