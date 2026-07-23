<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (! Schema::hasColumn('candidates', 'public_code')) {
                $table->string('public_code', 40)->nullable()->index()->after('id');
            }
            if (! Schema::hasColumn('candidates', 'public_code_meta')) {
                $table->json('public_code_meta')->nullable()->after('public_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'public_code_meta')) {
                $table->dropColumn('public_code_meta');
            }
            if (Schema::hasColumn('candidates', 'public_code')) {
                $table->dropColumn('public_code');
            }
        });
    }
};
