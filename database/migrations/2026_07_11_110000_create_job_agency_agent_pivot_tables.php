<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot tables used by Job↔Agency / Sub-Agency / Agent assignment.
     * These existed on some local DBs without migrations — production was missing them.
     */
    public function up(): void
    {
        if (! Schema::hasTable('job_agencies')) {
            Schema::create('job_agencies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('agency_id')->nullable();
                $table->unique(['job_id', 'agency_id']);
            });
        }

        if (! Schema::hasTable('job_sub_agencies')) {
            Schema::create('job_sub_agencies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('sub_agency_id')->nullable();
                $table->boolean('hide_company_name')->default(false);
                $table->boolean('hide_salary')->default(false);
                $table->boolean('hide_city')->default(false);
                $table->boolean('hide_country')->default(false);
                $table->boolean('hide_company_logo')->default(false);
                $table->boolean('hide_job_description')->default(false);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('job_agents')) {
            Schema::create('job_agents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('agent_id')->nullable();
                $table->boolean('hide_company_name')->default(false);
                $table->boolean('hide_salary')->default(false);
                $table->boolean('hide_city')->default(false);
                $table->boolean('hide_country')->default(false);
                $table->boolean('hide_company_logo')->default(false);
                $table->boolean('hide_job_description')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_agents');
        Schema::dropIfExists('job_sub_agencies');
        Schema::dropIfExists('job_agencies');
    }
};
