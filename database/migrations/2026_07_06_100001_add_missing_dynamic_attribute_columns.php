<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('candidate_attributes')) {
            Schema::table('candidate_attributes', function (Blueprint $table) {
                if (! Schema::hasColumn('candidate_attributes', 'options')) {
                    $table->text('options')->nullable();
                }
                if (! Schema::hasColumn('candidate_attributes', 'attribute_value')) {
                    $table->text('attribute_value')->nullable();
                }
                if (! Schema::hasColumn('candidate_attributes', 'input_type')) {
                    $table->string('input_type', 32)->default('text');
                }
                if (! Schema::hasColumn('candidate_attributes', 'is_required')) {
                    $table->tinyInteger('is_required')->default(0);
                }
                if (! Schema::hasColumn('candidate_attributes', 'is_active')) {
                    $table->tinyInteger('is_active')->default(1);
                }
            });
        }
    }

    public function down(): void
    {
        //
    }
};
