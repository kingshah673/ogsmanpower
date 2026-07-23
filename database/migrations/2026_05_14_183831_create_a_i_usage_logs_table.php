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
        Schema::create('ai_usage_logs', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('user_id')->nullable();

    $table->string('module')->nullable();

    $table->string('model')->nullable();

    $table->integer('prompt_tokens')->default(0);

    $table->integer('completion_tokens')->default(0);

    $table->integer('total_tokens')->default(0);

    $table->decimal('cost',10,4)->default(0);

    $table->longText('prompt')->nullable();

    $table->longText('response')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('a_i_usage_logs');
    }
};
