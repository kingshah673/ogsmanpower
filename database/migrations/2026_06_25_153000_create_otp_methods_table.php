<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('otp_methods')) {
            return;
        }

        Schema::create('otp_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        // IDs 1 and 3 are hardcoded in the seeder migration (2026_06_25_154443)
        DB::table('otp_methods')->insert([
            ['id' => 1, 'name' => 'email',    'display_name' => 'Email OTP',    'is_active' => true,  'config' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'sms',      'display_name' => 'SMS OTP',      'is_active' => false, 'config' => null, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'whatsapp', 'display_name' => 'WhatsApp OTP', 'is_active' => true,  'config' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_methods');
    }
};
