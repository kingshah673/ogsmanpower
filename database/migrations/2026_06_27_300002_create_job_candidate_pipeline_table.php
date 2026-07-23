<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('job_candidate_pipeline')) {
            Schema::create('job_candidate_pipeline', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id')->nullable();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('agency_id')->nullable();
                $table->string('status')->default('shortlisted');
                $table->string('hiring_status')->default('not_started');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_candidate_pipeline');
    }
};
