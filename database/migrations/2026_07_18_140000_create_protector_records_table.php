<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('protector_records')) {
            Schema::create('protector_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id')->index();
                $table->unsignedBigInteger('agency_id')->nullable()->index();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->unsignedBigInteger('applied_job_id')->nullable()->index();
                $table->string('reference_number')->nullable();
                $table->string('submission_status', 30)->default('not_submitted'); // not_submitted|submitted|under_review
                $table->string('clearance_status', 30)->default('pending'); // pending|cleared|rejected
                $table->string('submission_file')->nullable();
                $table->string('clearance_file')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->date('expiry_date')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('cleared_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('protector_records');
    }
};
