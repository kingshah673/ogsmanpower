<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('company_invitations')) {
            return;
        }

        Schema::create('company_invitations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agency_id');     // agencies.id of inviting agency
            $table->string('invitation_type');           // 'company' | 'agency'
            $table->string('company_name');
            $table->string('company_email');
            $table->string('whatsapp')->nullable();
            $table->text('message')->nullable();
            $table->string('token', 64)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_invitations');
    }
};
