<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vp_cases', function (Blueprint $table) {
            if (! Schema::hasColumn('vp_cases', 'flight_airline')) {
                $table->string('flight_airline')->nullable()->after('completed_at');
            }
            if (! Schema::hasColumn('vp_cases', 'flight_ticket_number')) {
                $table->string('flight_ticket_number')->nullable()->after('flight_airline');
            }
            if (! Schema::hasColumn('vp_cases', 'flight_date')) {
                $table->date('flight_date')->nullable()->after('flight_ticket_number');
            }
            if (! Schema::hasColumn('vp_cases', 'deployed_at')) {
                $table->timestamp('deployed_at')->nullable()->after('flight_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vp_cases', function (Blueprint $table) {
            foreach (['flight_airline', 'flight_ticket_number', 'flight_date', 'deployed_at'] as $col) {
                if (Schema::hasColumn('vp_cases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
