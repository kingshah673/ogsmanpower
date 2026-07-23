<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bilangual_resume_subscriptions')) {
            Schema::create('bilangual_resume_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->string('language_code', 10);
                $table->string('language_name', 100)->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->string('payment_method', 50)->nullable();
                $table->string('payment_reference', 255)->nullable();
                $table->decimal('price', 10, 2)->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                $table->foreign('approved_by')->references('id')->on('admins')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bilangual_resume_subscriptions');
    }
};
