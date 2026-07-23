<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workflow_stages')) {
            Schema::create('workflow_stages', function (Blueprint $table) {
                $table->id();
                $table->string('stage_key')->unique();
                $table->string('name');
                $table->unsignedTinyInteger('stage_order');
                $table->unsignedTinyInteger('weight')->default(20);
                $table->unsignedSmallInteger('sla_days')->default(7);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('workflow_stages') && DB::table('workflow_stages')->count() === 0) {
            $now = now();
            DB::table('workflow_stages')->insert([
                [
                    'stage_key'   => 'job_posted',
                    'name'        => 'Job Posted',
                    'stage_order' => 1,
                    'weight'      => 20,
                    'sla_days'    => 7,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'stage_key'   => 'documents',
                    'name'        => 'Documents',
                    'stage_order' => 2,
                    'weight'      => 20,
                    'sla_days'    => 7,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'stage_key'   => 'interview',
                    'name'        => 'Interview',
                    'stage_order' => 3,
                    'weight'      => 20,
                    'sla_days'    => 7,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'stage_key'   => 'visa',
                    'name'        => 'Visa Processing',
                    'stage_order' => 4,
                    'weight'      => 20,
                    'sla_days'    => 14,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'stage_key'   => 'deployed',
                    'name'        => 'Deployed',
                    'stage_order' => 5,
                    'weight'      => 20,
                    'sla_days'    => 0,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
            ]);
        }

        if (Schema::hasTable('visa_cases') && ! Schema::hasColumn('visa_cases', 'stage_started_at')) {
            Schema::table('visa_cases', function (Blueprint $table) {
                $table->timestamp('stage_started_at')->nullable()->after('current_stage_key');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('visa_cases') && Schema::hasColumn('visa_cases', 'stage_started_at')) {
            Schema::table('visa_cases', function (Blueprint $table) {
                $table->dropColumn('stage_started_at');
            });
        }

        Schema::dropIfExists('workflow_stages');
    }
};
