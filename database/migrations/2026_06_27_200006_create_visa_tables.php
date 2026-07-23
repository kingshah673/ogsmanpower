<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visa_cases')) {
            Schema::create('visa_cases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id')->nullable();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->unsignedBigInteger('agency_id')->nullable();
                $table->unsignedBigInteger('agent_id')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->string('country')->nullable();
                $table->string('visa_type')->nullable();
                $table->string('status')->default('pending');
                $table->integer('stage_order')->default(0);
                $table->string('current_stage_key')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('visa_case_logs')) {
            Schema::create('visa_case_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('case_id');
                $table->string('action');
                $table->text('description')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();

                $table->foreign('case_id')->references('id')->on('visa_cases')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('visa_documents')) {
            Schema::create('visa_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('case_id');
                $table->string('document_name');
                $table->string('file_path');
                $table->string('status')->default('pending');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->unsignedBigInteger('verified_by')->nullable();
                $table->timestamps();

                $table->foreign('case_id')->references('id')->on('visa_cases')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('case_tasks')) {
            Schema::create('case_tasks', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('case_id');
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->string('role')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status')->default('pending');
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->foreign('case_id')->references('id')->on('visa_cases')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_tasks');
        Schema::dropIfExists('visa_documents');
        Schema::dropIfExists('visa_case_logs');
        Schema::dropIfExists('visa_cases');
    }
};
