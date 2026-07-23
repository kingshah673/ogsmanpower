<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        Artisan::call('admin:sync-permissions', [
            '--email' => 'ogs.consultant@gmail.com',
        ]);
    }

    public function down(): void
    {
        // Permissions are not removed on rollback.
    }
};
