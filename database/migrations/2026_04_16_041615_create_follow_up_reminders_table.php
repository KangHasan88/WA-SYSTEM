<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_reminders', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('wa_inbox_id')->unsigned();
            $table->string('customer_number', 20);
            $table->string('customer_name', 100)->nullable();
            $table->datetime('reminder_date');
            $table->text('reminder_note')->nullable();
            $table->string('status', 20)->default('pending'); // pending, done, cancelled
            $table->datetime('done_at')->nullable();
            $table->string('done_by', 100)->nullable();
            $table->timestamps();
            
            $table->index('reminder_date');
            $table->index('status');
            $table->index('customer_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_reminders');
    }
};