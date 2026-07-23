<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_chat_messages')) {
            Schema::create('ai_chat_messages', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->nullable()->index();
                $table->text('user_message')->nullable();
                $table->text('ai_reply')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('source')->nullable();
                $table->string('sender')->nullable();
                $table->boolean('is_admin')->default(false);
                $table->boolean('human_mode')->default(false);
                $table->string('attachment')->nullable();
                $table->string('voice_message')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_handover_requests')) {
            Schema::create('ai_handover_requests', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->nullable()->index();
                $table->text('user_message')->nullable();
                $table->string('status')->default('pending');
                $table->text('admin_reply')->nullable();
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_knowledge_base')) {
            Schema::create('ai_knowledge_base', function (Blueprint $table) {
                $table->id();
                $table->string('title')->nullable();
                $table->text('question');
                $table->text('answer');
                $table->string('category')->nullable();
                $table->string('intent')->nullable();
                $table->text('keywords')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('ai_notifications')) {
            Schema::create('ai_notifications', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('message');
                $table->string('type')->nullable();
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('chatbot_sessions')) {
            Schema::create('chatbot_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('session_id')->nullable()->unique();
                $table->json('data')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_sessions');
        Schema::dropIfExists('ai_notifications');
        Schema::dropIfExists('ai_knowledge_base');
        Schema::dropIfExists('ai_handover_requests');
        Schema::dropIfExists('ai_chat_messages');
    }
};
