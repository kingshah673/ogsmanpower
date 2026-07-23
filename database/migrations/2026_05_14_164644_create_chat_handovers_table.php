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
        Schema::create('chat_handovers', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('chatbot_session_id');

    $table->unsignedBigInteger('user_id')->nullable();

    $table->string('channel')->nullable();

    $table->text('reason')->nullable();

    $table->enum('status', [

        'pending',

        'assigned',

        'resolved'

    ])->default('pending');

    $table->unsignedBigInteger('assigned_to')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_handovers');
    }
};
