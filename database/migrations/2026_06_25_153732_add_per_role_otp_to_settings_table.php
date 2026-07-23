<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'candidate_email_otp')) $table->boolean('candidate_email_otp')->default(1);
            if (!Schema::hasColumn('settings', 'candidate_whatsapp_otp')) $table->boolean('candidate_whatsapp_otp')->default(1);
            if (!Schema::hasColumn('settings', 'employer_email_otp')) $table->boolean('employer_email_otp')->default(1);
            if (!Schema::hasColumn('settings', 'employer_whatsapp_otp')) $table->boolean('employer_whatsapp_otp')->default(1);
        });

        // Seed new columns from existing global settings
        $existing = DB::table('settings')->first();
        if ($existing) {
            DB::table('settings')->update([
                'candidate_email_otp'    => $existing->email_otp_verification ?? 1,
                'candidate_whatsapp_otp' => $existing->whatsapp_otp_verification ?? 1,
                'employer_email_otp'     => $existing->email_otp_verification ?? 1,
                'employer_whatsapp_otp'  => $existing->whatsapp_otp_verification ?? 1,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'candidate_email_otp',
                'candidate_whatsapp_otp',
                'employer_email_otp',
                'employer_whatsapp_otp',
            ]);
        });
    }
};
