<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_chat_rooms', function (Blueprint $table) {

    $table->id();

    $table->string('phone')->unique();

    $table->string('name')->nullable();

    $table->enum('status',[

        'ai',

        'human'

    ])->default('ai');

    $table->timestamp('last_message_at')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_chat_rooms');
    }
};
