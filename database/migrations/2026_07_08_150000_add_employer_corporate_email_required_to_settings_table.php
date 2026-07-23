<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('settings', 'employer_corporate_email_required')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->boolean('employer_corporate_email_required')->default(true)->after('employer_auto_activation');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('settings', 'employer_corporate_email_required')) {
            Schema::table('settings', function (Blueprint $table) {
                $table->dropColumn('employer_corporate_email_required');
            });
        }
    }
};
