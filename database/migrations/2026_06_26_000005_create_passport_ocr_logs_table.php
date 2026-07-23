<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passport_ocr_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->longText('raw_ocr_text')->nullable();
            $table->json('extracted_fields')->nullable();
            $table->json('existing_db_fields')->nullable();
            $table->json('conflicts')->nullable();
            $table->enum('status', ['pending_review', 'confirmed', 'rejected'])->default('pending_review');
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->string('ocr_engine', 50)->default('ocr.space');
            $table->timestamps();

            $table->foreign('document_id')->references('id')->on('candidate_documents')->nullOnDelete();
            $table->foreign('confirmed_by')->references('id')->on('users')->nullOnDelete();
            $table->index('candidate_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passport_ocr_logs');
    }
};
