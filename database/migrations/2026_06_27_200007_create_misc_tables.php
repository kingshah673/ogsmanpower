<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('chat_leads')) {
            Schema::create('chat_leads', function (Blueprint $table) {
                $table->id();
                $table->string('full_name')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('session_id')->nullable()->index();
                $table->string('source')->nullable();
                $table->string('category')->nullable();
                $table->string('status')->default('new');
                $table->string('priority')->default('normal');
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->text('message')->nullable();
                $table->string('country')->nullable();
                $table->string('city')->nullable();
                $table->timestamp('next_followup_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('commissions')) {
            Schema::create('commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('contract_id')->nullable();
                $table->unsignedBigInteger('agent_id')->nullable();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('type')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('device_tokens')) {
            Schema::create('device_tokens', function (Blueprint $table) {
                $table->id();
                $table->string('device_token');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('hire_requests')) {
            Schema::create('hire_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id');
                $table->unsignedBigInteger('company_id');
                $table->text('message')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('job_ai_templates')) {
            Schema::create('job_ai_templates', function (Blueprint $table) {
                $table->id();
                $table->string('job_title');
                $table->text('description')->nullable();
                $table->json('skills')->nullable();
                $table->json('tags')->nullable();
                $table->string('min_salary')->nullable();
                $table->string('max_salary')->nullable();
                $table->string('experience')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('job_assignments')) {
            Schema::create('job_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('assigned_to_agent_id')->nullable();
                $table->unsignedBigInteger('assigned_to_agency_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('job_requirements')) {
            Schema::create('job_requirements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('candidate_id')->nullable();
                $table->json('jobs')->nullable();
                $table->json('industries')->nullable();
                $table->string('region')->nullable();
                $table->string('currency')->nullable();
                $table->string('salary')->nullable();
                $table->unsignedBigInteger('search_country_id')->nullable();
                $table->unsignedBigInteger('state_id')->nullable();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('whatsapp_leads')) {
            Schema::create('whatsapp_leads', function (Blueprint $table) {
                $table->id();
                $table->string('phone');
                $table->string('name')->nullable();
                $table->text('last_message')->nullable();
                $table->string('status')->default('new');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_leads');
        Schema::dropIfExists('job_requirements');
        Schema::dropIfExists('job_assignments');
        Schema::dropIfExists('job_ai_templates');
        Schema::dropIfExists('hire_requests');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('chat_leads');
    }
};
