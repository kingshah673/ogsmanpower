<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('visa_flows')) {
            Schema::create('visa_flows', function (Blueprint $table) {
                $table->id();
                $table->string('country_name');
                $table->unsignedBigInteger('search_country_id')->nullable()->index();
                $table->string('visa_type')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('visa_flow_steps')) {
            Schema::create('visa_flow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visa_flow_id')->constrained('visa_flows')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('assignee', 20); // employer|seeker
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('visa_flow_requirements')) {
            Schema::create('visa_flow_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('visa_flow_step_id')->constrained('visa_flow_steps')->cascadeOnDelete();
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->string('label');
                $table->string('type', 20); // file|text|date|checkbox
                $table->boolean('is_required')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vp_cases')) {
            Schema::create('vp_cases', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('visa_flow_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('agency_id')->nullable()->index();
                $table->unsignedBigInteger('candidate_id')->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->unsignedBigInteger('applied_job_id')->nullable()->index();
                $table->string('country_name');
                $table->string('status', 30)->default('in_progress'); // in_progress|completed|cancelled
                $table->unsignedInteger('current_step_index')->default(0);
                $table->unsignedBigInteger('started_by')->nullable();
                $table->text('cancel_reason')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vp_case_steps')) {
            Schema::create('vp_case_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vp_case_id')->constrained('vp_cases')->cascadeOnDelete();
                $table->unsignedBigInteger('source_step_id')->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('assignee', 20);
                $table->unsignedInteger('sort_order')->default(0);
                $table->string('status', 30)->default('pending'); // pending|active|completed|rejected|frozen
                $table->text('rejection_reason')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vp_case_requirements')) {
            Schema::create('vp_case_requirements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vp_case_step_id')->constrained('vp_case_steps')->cascadeOnDelete();
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->unsignedBigInteger('source_requirement_id')->nullable();
                $table->string('label');
                $table->string('type', 20);
                $table->boolean('is_required')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vp_case_answers')) {
            Schema::create('vp_case_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vp_case_requirement_id')->constrained('vp_case_requirements')->cascadeOnDelete();
                $table->unsignedBigInteger('submitted_by')->nullable();
                $table->text('value')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vp_case_files')) {
            Schema::create('vp_case_files', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vp_case_id')->constrained('vp_cases')->cascadeOnDelete();
                $table->foreignId('vp_case_requirement_id')->constrained('vp_case_requirements')->cascadeOnDelete();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('original_name');
                $table->string('path');
                $table->unsignedBigInteger('size')->default(0);
                $table->string('mime')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('vp_case_events')) {
            Schema::create('vp_case_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vp_case_id')->constrained('vp_cases')->cascadeOnDelete();
                $table->string('event_type');
                $table->text('message')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nominated_workers')) {
            Schema::create('nominated_workers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('agency_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->string('full_name');
                $table->string('passport_number')->nullable()->index();
                $table->string('nationality')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('gender', 20)->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('destination_country')->nullable();
                $table->string('job_title')->nullable();
                $table->string('status', 40)->default('pending_docs');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nominated_worker_documents')) {
            Schema::create('nominated_worker_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('nominated_worker_id')->nullable()->constrained('nominated_workers')->nullOnDelete();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->string('label')->nullable();
                $table->string('original_name');
                $table->string('path');
                $table->unsignedBigInteger('size')->default(0);
                $table->string('mime')->nullable();
                $table->longText('ocr_raw_text')->nullable();
                $table->json('extracted_fields')->nullable();
                $table->unsignedBigInteger('matched_worker_id')->nullable()->index();
                $table->decimal('match_confidence', 5, 2)->nullable();
                $table->string('match_status', 30)->default('unmatched');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nominated_worker_documents');
        Schema::dropIfExists('nominated_workers');
        Schema::dropIfExists('vp_case_events');
        Schema::dropIfExists('vp_case_files');
        Schema::dropIfExists('vp_case_answers');
        Schema::dropIfExists('vp_case_requirements');
        Schema::dropIfExists('vp_case_steps');
        Schema::dropIfExists('vp_cases');
        Schema::dropIfExists('visa_flow_requirements');
        Schema::dropIfExists('visa_flow_steps');
        Schema::dropIfExists('visa_flows');
    }
};
