<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidate_contracts')) {
            Schema::create('candidate_contracts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('pipeline_id')->nullable();
                $table->unsignedBigInteger('candidate_id')->nullable();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('contract_title')->nullable();
                $table->longText('contract_details')->nullable();
                $table->string('salary')->nullable();
                $table->string('duty_hours')->nullable();
                $table->string('contract_duration')->nullable();
                $table->string('location')->nullable();
                $table->string('status')->default('sent');
                $table->timestamp('candidate_signed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_contracts');
    }
};
