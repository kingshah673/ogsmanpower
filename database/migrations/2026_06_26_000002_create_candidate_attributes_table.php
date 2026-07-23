<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('candidate_attributes')) {
            Schema::create('candidate_attributes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('candidate_id')->constrained('candidates')->cascadeOnDelete();
                $table->string('attribute_name', 255);
                $table->enum('input_type', ['text', 'textarea', 'date', 'dropdown', 'file'])->default('text');
                $table->text('attribute_value')->nullable();
                $table->text('options')->nullable();
                $table->tinyInteger('is_required')->default(0);
                $table->tinyInteger('is_active')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_attributes');
    }
};
