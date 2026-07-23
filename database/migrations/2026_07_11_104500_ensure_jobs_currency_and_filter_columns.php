<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure job-post salary/filter columns exist (currency was used in forms
     * but never migrated — breaks manual job posting on production).
     */
    public function up(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            if (! Schema::hasColumn('jobs', 'currency')) {
                $table->string('currency', 10)->nullable()->default('USD')->after('max_salary');
            }
            if (! Schema::hasColumn('jobs', 'min_age')) {
                $table->unsignedSmallInteger('min_age')->nullable()->after('currency');
            }
            if (! Schema::hasColumn('jobs', 'max_age')) {
                $table->unsignedSmallInteger('max_age')->nullable()->after('min_age');
            }
            if (! Schema::hasColumn('jobs', 'gender')) {
                $table->string('gender', 20)->nullable()->after('max_age');
            }
            if (! Schema::hasColumn('jobs', 'city_limit')) {
                $table->boolean('city_limit')->default(0);
            }
            if (! Schema::hasColumn('jobs', 'education_limit')) {
                $table->boolean('education_limit')->default(0);
            }
            if (! Schema::hasColumn('jobs', 'experience_limit')) {
                $table->boolean('experience_limit')->default(0);
            }
            if (! Schema::hasColumn('jobs', 'age_limit')) {
                $table->boolean('age_limit')->default(0);
            }
            if (! Schema::hasColumn('jobs', 'gender_limit')) {
                $table->boolean('gender_limit')->default(0);
            }
            if (! Schema::hasColumn('jobs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }
            if (! Schema::hasColumn('jobs', 'ip_country')) {
                $table->string('ip_country', 100)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('jobs')) {
            return;
        }

        Schema::table('jobs', function (Blueprint $table) {
            $drop = [];
            foreach ([
                'currency', 'min_age', 'max_age', 'gender',
                'city_limit', 'education_limit', 'experience_limit',
                'age_limit', 'gender_limit', 'ip_address', 'ip_country',
            ] as $col) {
                if (Schema::hasColumn('jobs', $col)) {
                    $drop[] = $col;
                }
            }
            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
};
