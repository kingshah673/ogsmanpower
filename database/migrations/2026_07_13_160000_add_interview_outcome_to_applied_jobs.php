<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('applied_jobs')) {
            return;
        }

        Schema::table('applied_jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('applied_jobs', 'interview_outcome')) {
                if (Schema::hasColumn('applied_jobs', 'interview_location')) {
                    $table->string('interview_outcome', 50)->nullable()->after('interview_location');
                } else {
                    $table->string('interview_outcome', 50)->nullable();
                }
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('applied_jobs') && Schema::hasColumn('applied_jobs', 'interview_outcome')) {
            Schema::table('applied_jobs', function (Blueprint $table) {
                $table->dropColumn('interview_outcome');
            });
        }
    }
};
