<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('contact_infos', 'whatsapp_number')) {
                $table->string('whatsapp_number')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('contact_infos', function (Blueprint $table) {
            if (Schema::hasColumn('contact_infos', 'whatsapp_number')) {
                $table->dropColumn('whatsapp_number');
            }
        });
    }
};
