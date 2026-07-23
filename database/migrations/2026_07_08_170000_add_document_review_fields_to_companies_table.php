<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'document_review_note')) {
                $table->text('document_review_note')->nullable()->after('document_verified_at');
            }
            if (! Schema::hasColumn('companies', 'documents_resubmit_required')) {
                $table->boolean('documents_resubmit_required')->default(false)->after('document_review_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            foreach (['document_review_note', 'documents_resubmit_required'] as $column) {
                if (Schema::hasColumn('companies', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
