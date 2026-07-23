<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->extendCandidateAttributes();
        $this->extendCompanyAttributes();
        $this->ensureCompanyAttributeTranslations();
    }

    private function extendCandidateAttributes(): void
    {
        if (! Schema::hasTable('candidate_attributes')) {
            return;
        }

        Schema::table('candidate_attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('candidate_attributes', 'section')) {
                $table->string('section', 64)->default('basic-info');
            }
            if (! Schema::hasColumn('candidate_attributes', 'definition_id')) {
                $table->unsignedBigInteger('definition_id')->nullable();
            }
            if (! Schema::hasColumn('candidate_attributes', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (! Schema::hasColumn('candidate_attributes', 'options')) {
                $table->text('options')->nullable();
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE candidate_attributes MODIFY candidate_id BIGINT UNSIGNED NULL');
            } catch (\Throwable $e) {
                //
            }
            try {
                DB::statement("ALTER TABLE candidate_attributes MODIFY input_type VARCHAR(32) NOT NULL DEFAULT 'text'");
            } catch (\Throwable $e) {
                //
            }
        }
    }

    private function extendCompanyAttributes(): void
    {
        if (! Schema::hasTable('company_attributes')) {
            Schema::create('company_attributes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable();
                $table->string('section', 64)->default('job_post');
                $table->string('attribute_name', 255);
                $table->string('input_type', 32)->default('text');
                $table->text('attribute_value')->nullable();
                $table->text('options')->nullable();
                $table->tinyInteger('is_required')->default(0);
                $table->tinyInteger('is_active')->default(1);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });

            return;
        }

        Schema::table('company_attributes', function (Blueprint $table) {
            if (! Schema::hasColumn('company_attributes', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
            }
            if (! Schema::hasColumn('company_attributes', 'section')) {
                $table->string('section', 64)->default('job_post');
            }
            if (! Schema::hasColumn('company_attributes', 'options')) {
                $table->text('options')->nullable();
            }
            if (! Schema::hasColumn('company_attributes', 'sort_order')) {
                $table->integer('sort_order')->default(0);
            }
            if (! Schema::hasColumn('company_attributes', 'is_required')) {
                $table->tinyInteger('is_required')->default(0);
            }
            if (! Schema::hasColumn('company_attributes', 'is_active')) {
                $table->tinyInteger('is_active')->default(1);
            }
        });
    }

    private function ensureCompanyAttributeTranslations(): void
    {
        if (Schema::hasTable('company_attribute_translations')) {
            return;
        }

        Schema::create('company_attribute_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('job_id')->nullable();
            $table->unsignedBigInteger('company_attribute_id');
            $table->text('attribute_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        //
    }
};
