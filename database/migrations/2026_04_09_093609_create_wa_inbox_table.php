<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wa_inbox', function (Blueprint $table) {
            $table->id();
            $table->string('from_number', 20)->index();
            $table->string('from_name')->nullable();
            $table->text('message');
            $table->string('message_id')->nullable()->unique();
            $table->string('type')->default('text'); // text, image, audio, document
            $table->string('media_url')->nullable();
            $table->string('caption')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_replied')->default(false);
            $table->timestamp('received_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('wa_inbox');
    }
};