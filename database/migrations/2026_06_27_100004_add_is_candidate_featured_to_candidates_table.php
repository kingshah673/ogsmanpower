<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('candidates', 'is_candidate_featured')) {
            return;
        }

        Schema::table('candidates', function (Blueprint $table) {
            $table->boolean('is_candidate_featured')->default(false)->after('profile_complete');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('is_candidate_featured');
        });
    }
};
