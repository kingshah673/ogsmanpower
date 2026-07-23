<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $privacyPolicy = require database_path('data/careerworkforce_privacy_policy.php');

        DB::table('cms')->update(['privary_page' => $privacyPolicy]);

        DB::table('cms_contents')
            ->where('page_slug', 'privacy_page')
            ->update(['text' => $privacyPolicy]);
    }

    public function down(): void
    {
        //
    }
};
