<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Keep applied_jobs (and other schema_ensure tables) aligned with controllers/models.
 * Delegates to `schema:ensure` so config/schema_ensure.php is the single source of truth.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('schema:ensure', [
            '--table' => 'applied_jobs',
        ]);
    }

    public function down(): void
    {
        // Non-destructive
    }
};
