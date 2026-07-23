<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('vp_case_files')) {
            Schema::table('vp_case_files', function (Blueprint $table) {
                if (! Schema::hasColumn('vp_case_files', 'review_status')) {
                    $table->string('review_status', 20)->nullable()->after('mime');
                }
                if (! Schema::hasColumn('vp_case_files', 'review_reason')) {
                    $table->text('review_reason')->nullable()->after('review_status');
                }
                if (! Schema::hasColumn('vp_case_files', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_reason');
                }
                if (! Schema::hasColumn('vp_case_files', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
            });
        }

        if (Schema::hasTable('vp_case_answers')) {
            Schema::table('vp_case_answers', function (Blueprint $table) {
                if (! Schema::hasColumn('vp_case_answers', 'review_status')) {
                    $table->string('review_status', 20)->nullable()->after('value');
                }
                if (! Schema::hasColumn('vp_case_answers', 'review_reason')) {
                    $table->text('review_reason')->nullable()->after('review_status');
                }
                if (! Schema::hasColumn('vp_case_answers', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_reason');
                }
                if (! Schema::hasColumn('vp_case_answers', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('vp_case_files')) {
            Schema::table('vp_case_files', function (Blueprint $table) {
                foreach (['reviewed_at', 'reviewed_by', 'review_reason', 'review_status'] as $col) {
                    if (Schema::hasColumn('vp_case_files', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('vp_case_answers')) {
            Schema::table('vp_case_answers', function (Blueprint $table) {
                foreach (['reviewed_at', 'reviewed_by', 'review_reason', 'review_status'] as $col) {
                    if (Schema::hasColumn('vp_case_answers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
