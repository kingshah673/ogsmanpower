<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nominated_worker_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('nominated_worker_documents', 'agency_id')) {
                $table->unsignedBigInteger('agency_id')->nullable()->index()->after('company_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nominated_worker_documents', function (Blueprint $table) {
            if (Schema::hasColumn('nominated_worker_documents', 'agency_id')) {
                $table->dropColumn('agency_id');
            }
        });
    }
};
