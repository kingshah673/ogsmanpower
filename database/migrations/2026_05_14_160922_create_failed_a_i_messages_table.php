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
        Schema::create('failed_ai_messages', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('user_id')->nullable();

    $table->string('channel')->nullable();

    $table->longText('question');

    $table->longText('ai_reply')->nullable();

    $table->string('intent')->nullable();

    $table->tinyInteger('resolved')->default(0);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_a_i_messages');
    }
};
