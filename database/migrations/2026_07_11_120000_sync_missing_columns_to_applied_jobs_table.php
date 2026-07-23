<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Align applied_jobs with local/admin apply fields used across environments.
 * Safe to re-run: only adds missing columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applied_jobs')) {
            return;
        }

        Schema::table('applied_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('applied_jobs', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('applied_jobs', 'resume_format')) {
                $table->string('resume_format')->nullable()->after('cover_letter');
            }
            if (! Schema::hasColumn('applied_jobs', 'cv_path')) {
                $table->string('cv_path')->nullable()->after('resume_format');
            }
            if (! Schema::hasColumn('applied_jobs', 'years')) {
                $table->integer('years')->default(0)->after('answers');
            }
            if (! Schema::hasColumn('applied_jobs', 'status')) {
                $table->string('status', 50)->default('pending');
            }
            if (! Schema::hasColumn('applied_jobs', 'order')) {
                $table->smallInteger('order')->default(0);
            }
        });

        // candidate_resume_id must be nullable for agency/agent submissions without a CV row
        if (Schema::hasColumn('applied_jobs', 'candidate_resume_id')) {
            try {
                Schema::table('applied_jobs', function (Blueprint $table) {
                    $table->unsignedBigInteger('candidate_resume_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // doctrine/dbal may be unavailable; column is already nullable on most envs
            }
        }
    }

    public function down(): void
    {
        // Non-destructive: do not drop columns that may hold production data.
    }
};
