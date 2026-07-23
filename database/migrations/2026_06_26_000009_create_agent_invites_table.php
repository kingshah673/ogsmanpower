<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agent_invites')) {
            return;
        }

        Schema::create('agent_invites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agency_user_id'); // user.id of the inviting agency
            $table->string('agent_name');
            $table->string('agent_email');
            $table->string('token', 64)->unique();
            $table->dateTime('accepted_at')->nullable();
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_invites');
    }
};
