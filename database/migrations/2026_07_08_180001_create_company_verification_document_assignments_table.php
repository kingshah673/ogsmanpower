<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migration may have partially created this table before FK names were shortened.
        Schema::dropIfExists('company_verification_document_assignments');

        Schema::create('company_verification_document_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('document_type_id');
            $table->boolean('is_required')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('company_id', 'cvda_company_id_fk')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign('document_type_id', 'cvda_doc_type_id_fk')
                ->references('id')
                ->on('employer_verification_document_types')
                ->cascadeOnDelete();

            $table->unique(['company_id', 'document_type_id'], 'cvda_company_doc_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_verification_document_assignments');
    }
};
