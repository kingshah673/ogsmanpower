<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enum cannot hold broker / specialty keys — switch to varchar.
        DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'candidate'");

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'signup_type')) {
                $table->string('signup_type', 50)->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'signup_type')) {
                $table->dropColumn('signup_type');
            }
        });

        // Restore a safe core enum (data may need cleanup if specialty roles exist).
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('company','candidate','agent','agency','broker') NOT NULL DEFAULT 'candidate'");
    }
};
