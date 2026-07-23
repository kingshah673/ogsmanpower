<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brokers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('organization_name')->nullable();
            $table->string('license_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('country')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('profile_completion')->default(false);
            $table->boolean('is_profile_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('broker_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('broker_id')->constrained('brokers')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->unsignedInteger('vacancies')->default(1);
            $table->string('salary_note')->nullable();
            $table->string('status')->default('draft'); // draft, open, routed, closed
            $table->foreignId('routed_agency_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('routed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broker_demands');
        Schema::dropIfExists('brokers');
    }
};
