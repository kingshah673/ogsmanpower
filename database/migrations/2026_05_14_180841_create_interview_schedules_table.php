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
        Schema::create('interview_schedules', function (Blueprint $table) {

    $table->id();

    $table->unsignedBigInteger('candidate_id');

    $table->unsignedBigInteger('job_id')->nullable();

    $table->unsignedBigInteger('company_id')->nullable();

    $table->unsignedBigInteger('created_by')->nullable();

    $table->dateTime('interview_at');

    $table->string('meeting_link')->nullable();

    $table->string('platform')->nullable();

    $table->enum('status', [

        'scheduled',

        'completed',

        'cancelled'

    ])->default('scheduled');

    $table->text('remarks')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interview_schedules');
    }
};
