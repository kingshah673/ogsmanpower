<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('earnings', 'agency_id')) {
            Schema::table('earnings', function (Blueprint $table) {
                $table->unsignedBigInteger('agency_id')->nullable()->after('company_id');
            });
        }

        if (Schema::hasColumn('earnings', 'company_id')) {
            Schema::table('earnings', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->change();
            });
        }

        if (
            Schema::hasTable('agencies')
            && Schema::hasColumn('earnings', 'agency_id')
        ) {
            Schema::table('earnings', function (Blueprint $table) {
                $table->foreign('agency_id')
                    ->references('id')
                    ->on('agencies')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('earnings', 'agency_id')) {
            Schema::table('earnings', function (Blueprint $table) {
                $table->dropForeign(['agency_id']);
                $table->dropColumn('agency_id');
            });
        }

        if (Schema::hasColumn('earnings', 'company_id')) {
            Schema::table('earnings', function (Blueprint $table) {
                $table->foreignId('company_id')->nullable(false)->change();
            });
        }
    }
};
