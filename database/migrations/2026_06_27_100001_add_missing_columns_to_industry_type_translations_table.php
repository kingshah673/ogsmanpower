<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('industry_type_translations', function (Blueprint $table) {
            if (!Schema::hasColumn('industry_type_translations', 'jobs_by_industry')) {
                $table->boolean('jobs_by_industry')->default(0)->after('locale');
            }
            if (!Schema::hasColumn('industry_type_translations', 'candidates_by_industry')) {
                $table->boolean('candidates_by_industry')->default(0)->after('jobs_by_industry');
            }
            if (!Schema::hasColumn('industry_type_translations', 'image')) {
                $table->string('image')->nullable()->after('candidates_by_industry');
            }
        });
    }

    public function down(): void
    {
        Schema::table('industry_type_translations', function (Blueprint $table) {
            foreach (['jobs_by_industry', 'candidates_by_industry', 'image'] as $col) {
                if (Schema::hasColumn('industry_type_translations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
