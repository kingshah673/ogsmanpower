<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidate_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('candidate_documents', 'review_status')) {
                $table->json('review_status')->nullable()->after('navtec_report');
            }
            if (! Schema::hasColumn('candidate_documents', 'review_notes')) {
                $table->json('review_notes')->nullable()->after('review_status');
            }
            if (! Schema::hasColumn('candidate_documents', 'medical_expiry_date')) {
                $table->date('medical_expiry_date')->nullable()->after('review_notes');
            }
            if (! Schema::hasColumn('candidate_documents', 'police_certificate_expiry_date')) {
                $table->date('police_certificate_expiry_date')->nullable()->after('medical_expiry_date');
            }
            if (! Schema::hasColumn('candidate_documents', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('police_certificate_expiry_date');
            }
            if (! Schema::hasColumn('candidate_documents', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidate_documents', function (Blueprint $table) {
            foreach (['review_status', 'review_notes', 'medical_expiry_date', 'police_certificate_expiry_date', 'reviewed_by', 'reviewed_at'] as $col) {
                if (Schema::hasColumn('candidate_documents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
