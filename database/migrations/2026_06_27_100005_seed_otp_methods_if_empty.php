<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('otp_methods')) {
            return;
        }

        // Only seed if no records exist (safe for both fresh and existing installs)
        foreach ([
            ['id' => 1, 'name' => 'email',    'display_name' => 'Email OTP',    'is_active' => true],
            ['id' => 2, 'name' => 'sms',      'display_name' => 'SMS OTP',      'is_active' => false],
            ['id' => 3, 'name' => 'whatsapp', 'display_name' => 'WhatsApp OTP', 'is_active' => true],
        ] as $method) {
            DB::table('otp_methods')->updateOrInsert(
                ['name' => $method['name']],
                array_merge($method, ['config' => null, 'created_at' => now(), 'updated_at' => now()])
            );
        }
    }

    public function down(): void
    {
        DB::table('otp_methods')->whereIn('name', ['email', 'sms', 'whatsapp'])->delete();
    }
};
