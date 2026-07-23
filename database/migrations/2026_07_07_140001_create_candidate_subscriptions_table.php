<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('candidate_subscriptions')) {
            Schema::create('candidate_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->foreignId('candidate_plan_id')->constrained('candidate_plans')->cascadeOnDelete();
                $table->unsignedInteger('duration')->nullable()->comment('Duration in days');
                $table->string('payment_type', 50)->nullable();
                $table->string('status', 50)->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_subscriptions');
    }
};
