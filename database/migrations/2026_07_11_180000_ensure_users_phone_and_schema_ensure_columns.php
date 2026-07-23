<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Ensure users.phone / contact_infos / applied_jobs columns from config/schema_ensure.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('schema:ensure');
    }

    public function down(): void
    {
        // Non-destructive
    }
};
