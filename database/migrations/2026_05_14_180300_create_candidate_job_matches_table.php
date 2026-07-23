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
        Schema::create('candidate_job_matches', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('candidate_id');

    $table->unsignedBigInteger('job_id');

    $table->decimal('score', 5, 2)->default(0);

    $table->json('skills')->nullable();

    $table->text('remarks')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_job_matches');
    }
};
