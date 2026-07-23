<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'address')) {
                $table->string('address')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'district')) {
                $table->string('district')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'region')) {
                $table->string('region')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'search_country_id')) {
                $table->unsignedBigInteger('search_country_id')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'job_role_id')) {
                $table->unsignedBigInteger('job_role_id')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'language_code')) {
                $table->string('language_code')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'resume_format')) {
                $table->string('resume_format')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'passport_number')) {
                $table->string('passport_number')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'passport_issue_date')) {
                $table->date('passport_issue_date')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'passport_expiry_date')) {
                $table->date('passport_expiry_date')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'place_of_issue')) {
                $table->string('place_of_issue')->nullable();
            }
            if (!Schema::hasColumn('candidates', 'cnic_number')) {
                $table->string('cnic_number')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $cols = [
                'address', 'district', 'region', 'country_id', 'search_country_id',
                'job_role_id', 'language_code', 'resume_format', 'passport_number',
                'passport_issue_date', 'passport_expiry_date', 'place_of_issue', 'cnic_number',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('candidates', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
