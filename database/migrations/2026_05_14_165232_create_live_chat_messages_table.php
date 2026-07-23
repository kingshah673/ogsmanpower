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
        Schema::create('live_chat_messages', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('chatbot_session_id');

    $table->unsignedBigInteger('sender_id')->nullable();

    $table->enum('sender_type', [

        'user',

        'agent',

        'ai'

    ]);

    $table->longText('message');

    $table->enum('channel', [

        'web',

        'whatsapp'

    ])->default('web');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('live_chat_messages');
    }
};
