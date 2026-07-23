<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The AI chat tables were created with the latin1 charset, so any 4‑byte
 * character (emoji such as 📎 🤖 ✅ used in Sophia replies, or non‑Latin
 * user input) throws "Incorrect string value" on insert — surfacing as a
 * 500 on POST /ai/chat. Convert them to utf8mb4 to match the rest of the DB.
 */
return new class extends Migration
{
    private array $tables = [
        'ai_chat_messages',
        'ai_handover_requests',
        'ai_notifications',
        'ai_knowledge_base',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return; // utf8mb4 conversion is MySQL/MariaDB specific
        }

        foreach ($this->tables as $table) {
            if (Schema::hasTable($table)) {
                DB::statement("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
        }
    }

    public function down(): void
    {
        // Intentionally a no-op: reverting to latin1 would re-introduce the
        // emoji/Unicode insert failures this migration fixes.
    }
};
