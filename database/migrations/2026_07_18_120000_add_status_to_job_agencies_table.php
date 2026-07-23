<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agency accept/decline workflow for employer job assignments (Phase 2
     * of the Recruitment Agency completion plan).
     */
    public function up(): void
    {
        Schema::table('job_agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('job_agencies', 'status')) {
                $table->string('status')->default('pending')->after('agency_id');
            }
            if (! Schema::hasColumn('job_agencies', 'decline_reason')) {
                $table->text('decline_reason')->nullable()->after('status');
            }
            if (! Schema::hasColumn('job_agencies', 'responded_at')) {
                $table->timestamp('responded_at')->nullable()->after('decline_reason');
            }
            if (! Schema::hasColumn('job_agencies', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_agencies', function (Blueprint $table) {
            $table->dropColumn(['status', 'decline_reason', 'responded_at']);
            if (Schema::hasColumn('job_agencies', 'created_at')) {
                $table->dropTimestamps();
            }
        });
    }
};
