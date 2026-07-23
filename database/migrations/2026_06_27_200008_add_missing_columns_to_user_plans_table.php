<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('user_plans', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable();
            }
            if (!Schema::hasColumn('user_plans', 'agency_id')) {
                $table->unsignedBigInteger('agency_id')->nullable();
            }
            if (!Schema::hasColumn('user_plans', 'agent_id')) {
                $table->unsignedBigInteger('agent_id')->nullable();
            }
            if (!Schema::hasColumn('user_plans', 'status')) {
                $table->string('status')->default('active');
            }
            if (!Schema::hasColumn('user_plans', 'expires_at')) {
                $table->timestamp('expires_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('user_plans', function (Blueprint $table) {
            foreach (['user_id', 'agency_id', 'agent_id', 'status', 'expires_at'] as $col) {
                if (Schema::hasColumn('user_plans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
