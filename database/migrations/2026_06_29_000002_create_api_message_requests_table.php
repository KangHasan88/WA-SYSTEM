<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_message_requests', function (Blueprint $table) {
            $table->id();
            $table->string('message_id')->unique();
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();
            $table->string('to_number');
            $table->text('message');
            $table->string('title')->nullable();
            $table->string('image_url')->nullable();
            $table->string('source')->nullable();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');
            $table->string('status')->default('queued');
            $table->timestamp('queued_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['source', 'reference_type', 'reference_id']);
            $table->index(['api_client_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_message_requests');
    }
};
