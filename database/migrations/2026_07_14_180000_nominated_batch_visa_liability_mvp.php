<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visa_flows')) {
            Schema::table('visa_flows', function (Blueprint $table) {
                if (! Schema::hasColumn('visa_flows', 'version')) {
                    $table->unsignedInteger('version')->default(1)->after('is_active');
                }
                if (! Schema::hasColumn('visa_flows', 'publish_status')) {
                    $table->string('publish_status', 20)->default('published')->after('version'); // draft|published
                }
            });
        }

        if (Schema::hasTable('visa_flow_steps') && ! Schema::hasColumn('visa_flow_steps', 'estimated_duration_days')) {
            Schema::table('visa_flow_steps', function (Blueprint $table) {
                $table->unsignedInteger('estimated_duration_days')->nullable()->after('sort_order');
            });
        }

        if (! Schema::hasTable('nominated_worker_batches')) {
            Schema::create('nominated_worker_batches', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('company_id')->index();
                $table->unsignedBigInteger('search_country_id')->nullable()->index();
                $table->string('country_name');
                $table->unsignedBigInteger('job_id')->nullable()->index();
                $table->unsignedBigInteger('visa_flow_id')->nullable()->index();
                $table->unsignedInteger('frozen_flow_version')->nullable();
                $table->unsignedBigInteger('agency_id')->nullable()->index();
                $table->string('assignment_mode', 20)->default('open_all'); // direct|open_all
                $table->string('status', 40)->default('draft');
                // draft|pending_approval|awaiting_agency|active|completed|cancelled|returned
                $table->text('admin_comment')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('nominated_batch_agency_responses')) {
            Schema::create('nominated_batch_agency_responses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('batch_id')->index();
                $table->unsignedBigInteger('agency_id')->index();
                $table->string('status', 20); // accepted|declined
                $table->string('reason')->nullable();
                $table->unsignedBigInteger('responded_by')->nullable();
                $table->timestamps();
                $table->unique(['batch_id', 'agency_id']);
            });
        }

        if (Schema::hasTable('nominated_workers') && ! Schema::hasColumn('nominated_workers', 'batch_id')) {
            Schema::table('nominated_workers', function (Blueprint $table) {
                $table->unsignedBigInteger('batch_id')->nullable()->index()->after('id');
            });
        }

        if (Schema::hasTable('vp_cases')) {
            Schema::table('vp_cases', function (Blueprint $table) {
                if (! Schema::hasColumn('vp_cases', 'nominated_worker_id')) {
                    $table->unsignedBigInteger('nominated_worker_id')->nullable()->index()->after('applied_job_id');
                }
            });
            // candidate_id already exists; nullability is application-level (MySQL may still be NOT NULL)
            try {
                Schema::table('vp_cases', function (Blueprint $table) {
                    $table->unsignedBigInteger('candidate_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // doctrine/dbal may be unavailable — tolerate; app treats null candidate for nominated cases
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vp_cases') && Schema::hasColumn('vp_cases', 'nominated_worker_id')) {
            Schema::table('vp_cases', function (Blueprint $table) {
                $table->dropColumn('nominated_worker_id');
            });
        }

        if (Schema::hasTable('nominated_workers') && Schema::hasColumn('nominated_workers', 'batch_id')) {
            Schema::table('nominated_workers', function (Blueprint $table) {
                $table->dropColumn('batch_id');
            });
        }

        Schema::dropIfExists('nominated_batch_agency_responses');
        Schema::dropIfExists('nominated_worker_batches');

        if (Schema::hasTable('visa_flow_steps') && Schema::hasColumn('visa_flow_steps', 'estimated_duration_days')) {
            Schema::table('visa_flow_steps', function (Blueprint $table) {
                $table->dropColumn('estimated_duration_days');
            });
        }

        if (Schema::hasTable('visa_flows')) {
            Schema::table('visa_flows', function (Blueprint $table) {
                if (Schema::hasColumn('visa_flows', 'publish_status')) {
                    $table->dropColumn('publish_status');
                }
                if (Schema::hasColumn('visa_flows', 'version')) {
                    $table->dropColumn('version');
                }
            });
        }
    }
};
