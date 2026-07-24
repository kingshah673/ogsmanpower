<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('about_story') && ! Schema::hasColumn('about_story', 'mission')) {
            Schema::table('about_story', function (Blueprint $table) {
                $table->text('mission')->nullable()->after('body_3');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('about_story') && Schema::hasColumn('about_story', 'mission')) {
            Schema::table('about_story', function (Blueprint $table) {
                $table->dropColumn('mission');
            });
        }
    }
};
