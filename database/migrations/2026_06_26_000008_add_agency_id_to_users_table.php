<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'agency_id')) {
                // Stores the id of the agency (user.id with role=agency) that owns this agent
                $table->unsignedBigInteger('agency_id')->nullable()->after('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'agency_id')) {
                $table->dropColumn('agency_id');
            }
        });
    }
};
