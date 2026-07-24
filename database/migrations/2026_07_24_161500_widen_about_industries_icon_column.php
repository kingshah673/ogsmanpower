<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('about_industries') && Schema::hasColumn('about_industries', 'icon')) {
            DB::statement('ALTER TABLE about_industries MODIFY icon VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('about_industries') && Schema::hasColumn('about_industries', 'icon')) {
            DB::statement('ALTER TABLE about_industries MODIFY icon VARCHAR(20) NULL');
        }
    }
};
