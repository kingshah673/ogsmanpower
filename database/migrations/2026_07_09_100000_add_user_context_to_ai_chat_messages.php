<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ai_chat_messages')) {
            return;
        }

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_chat_messages', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('session_id')->index();
            }
            if (! Schema::hasColumn('ai_chat_messages', 'portal_role')) {
                $table->string('portal_role', 32)->nullable()->after('user_id')->index();
            }
            if (! Schema::hasColumn('ai_chat_messages', 'admin_id')) {
                $table->unsignedBigInteger('admin_id')->nullable()->after('portal_role')->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ai_chat_messages')) {
            return;
        }

        Schema::table('ai_chat_messages', function (Blueprint $table) {
            foreach (['admin_id', 'portal_role', 'user_id'] as $column) {
                if (Schema::hasColumn('ai_chat_messages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
