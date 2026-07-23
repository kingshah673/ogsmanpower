<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            if (! Schema::hasColumn('commissions', 'agency_id')) {
                $table->unsignedBigInteger('agency_id')->nullable()->after('agent_id');
            }
            if (! Schema::hasColumn('commissions', 'applied_job_id')) {
                $table->unsignedBigInteger('applied_job_id')->nullable()->after('agency_id');
            }
            if (! Schema::hasColumn('commissions', 'candidate_id')) {
                $table->unsignedBigInteger('candidate_id')->nullable()->after('applied_job_id');
            }
            if (! Schema::hasColumn('commissions', 'job_id')) {
                $table->unsignedBigInteger('job_id')->nullable()->after('candidate_id');
            }
            if (! Schema::hasColumn('commissions', 'rate')) {
                $table->decimal('rate', 5, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('commissions', 'currency')) {
                $table->string('currency', 10)->nullable()->after('rate');
            }
            if (! Schema::hasColumn('commissions', 'notes')) {
                $table->text('notes')->nullable()->after('status');
            }
            if (! Schema::hasColumn('commissions', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('notes');
            }
        });

        try {
            Schema::table('commissions', function (Blueprint $table) {
                $table->unsignedBigInteger('contract_id')->nullable()->change();
            });
        } catch (\Throwable $e) {
            // doctrine/dbal may be unavailable — tolerate; new commissions can be created without a contract yet.
        }
    }

    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            foreach (['agency_id', 'applied_job_id', 'candidate_id', 'job_id', 'rate', 'currency', 'notes', 'paid_at'] as $col) {
                if (Schema::hasColumn('commissions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
