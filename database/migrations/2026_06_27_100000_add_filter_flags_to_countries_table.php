<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            if (!Schema::hasColumn('countries', 'candidates_by_country')) {
                $table->boolean('candidates_by_country')->default(0)->after('status');
            }
            if (!Schema::hasColumn('countries', 'jobs_by_country')) {
                $table->boolean('jobs_by_country')->default(0)->after('candidates_by_country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            foreach (['candidates_by_country', 'jobs_by_country'] as $col) {
                if (Schema::hasColumn('countries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
