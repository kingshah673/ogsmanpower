<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('applied_jobs', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable()->after('job_id');
            }
            if (! Schema::hasColumn('applied_jobs', 'agency_id')) {
                $table->unsignedBigInteger('agency_id')->nullable()->after('company_id');
            }
            if (! Schema::hasColumn('applied_jobs', 'status')) {
                $table->string('status', 50)->default('pending')->after('agency_id');
            }
        });

        if (Schema::hasTable('jobs') && Schema::hasColumn('applied_jobs', 'company_id')) {
            DB::statement('
                UPDATE applied_jobs aj
                INNER JOIN jobs j ON aj.job_id = j.id
                SET aj.company_id = j.company_id,
                    aj.agency_id = COALESCE(aj.agency_id, j.agency_id)
            ');
        }

        // Imported DBs may already have company_id as NOT NULL — FK with nullOnDelete requires nullable.
        if (Schema::hasColumn('applied_jobs', 'company_id')) {
            try {
                DB::statement('ALTER TABLE applied_jobs MODIFY company_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // ignore if already nullable / engine quirks
            }
        }
        if (Schema::hasColumn('applied_jobs', 'agency_id')) {
            try {
                DB::statement('ALTER TABLE applied_jobs MODIFY agency_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('companies') && Schema::hasColumn('applied_jobs', 'company_id')) {
            Schema::table('applied_jobs', function (Blueprint $table) {
                try {
                    $table->foreign('company_id')
                        ->references('id')
                        ->on('companies')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // FK may already exist
                }
            });
        }

        if (Schema::hasTable('agencies') && Schema::hasColumn('applied_jobs', 'agency_id')) {
            Schema::table('applied_jobs', function (Blueprint $table) {
                try {
                    $table->foreign('agency_id')
                        ->references('id')
                        ->on('agencies')
                        ->nullOnDelete();
                } catch (\Throwable $e) {
                    // FK may already exist
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('applied_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('applied_jobs', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            }
            if (Schema::hasColumn('applied_jobs', 'agency_id')) {
                $table->dropForeign(['agency_id']);
                $table->dropColumn('agency_id');
            }
            if (Schema::hasColumn('applied_jobs', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
