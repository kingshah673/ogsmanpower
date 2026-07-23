<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('frontend_show');
            }

            if (! Schema::hasColumn('plans', 'user_type')) {
                $table->string('user_type', 32)->default('company')->after('label');
            }

            if (! Schema::hasColumn('plans', 'duration_days')) {
                $table->unsignedInteger('duration_days')->default(30)->after('price');
            }

            if (! Schema::hasColumn('plans', 'stripe_price_id')) {
                $table->string('stripe_price_id')->nullable()->after('duration_days');
            }
        });

        if (Schema::hasColumn('plans', 'is_active')) {
            DB::table('plans')->whereNull('is_active')->update(['is_active' => 1]);
        }

        if (Schema::hasColumn('plans', 'user_type')) {
            DB::table('plans')
                ->whereNull('user_type')
                ->orWhere('user_type', '')
                ->update(['user_type' => 'company']);
        }
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            foreach (['is_active', 'user_type', 'duration_days', 'stripe_price_id'] as $column) {
                if (Schema::hasColumn('plans', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
