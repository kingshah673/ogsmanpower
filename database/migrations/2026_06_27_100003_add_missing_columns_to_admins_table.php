<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (!Schema::hasColumn('admins', 'username')) {
                $table->string('username')->nullable();
            }
            if (!Schema::hasColumn('admins', 'country')) {
                $table->string('country')->nullable();
            }
            if (!Schema::hasColumn('admins', 'whatsapp')) {
                $table->string('whatsapp')->nullable();
            }
            if (!Schema::hasColumn('admins', 'region')) {
                $table->string('region')->nullable();
            }
            if (!Schema::hasColumn('admins', 'district')) {
                $table->string('district')->nullable();
            }
            if (!Schema::hasColumn('admins', 'bio')) {
                $table->text('bio')->nullable();
            }
            if (!Schema::hasColumn('admins', 'website')) {
                $table->string('website')->nullable();
            }
            if (!Schema::hasColumn('admins', 'industry_type_id')) {
                $table->unsignedBigInteger('industry_type_id')->nullable();
            }
            if (!Schema::hasColumn('admins', 'lisence_image')) {
                $table->string('lisence_image')->nullable();
            }
            if (!Schema::hasColumn('admins', 'passport_image')) {
                $table->string('passport_image')->nullable();
            }
            if (!Schema::hasColumn('admins', 'id_card_image')) {
                $table->string('id_card_image')->nullable();
            }
            if (!Schema::hasColumn('admins', 'comapny_certificate_image')) {
                $table->string('comapny_certificate_image')->nullable();
            }
            if (!Schema::hasColumn('admins', 'is_profile_compeleted')) {
                $table->boolean('is_profile_compeleted')->default(false);
            }
            if (!Schema::hasColumn('admins', 'otp_code')) {
                $table->string('otp_code')->nullable();
            }
            if (!Schema::hasColumn('admins', 'otp_expiry')) {
                $table->timestamp('otp_expiry')->nullable();
            }
            if (!Schema::hasColumn('admins', 'is_otp_verified')) {
                $table->boolean('is_otp_verified')->default(false);
            }
        });
    }

    public function down(): void
    {
        $cols = [
            'username', 'country', 'whatsapp', 'region', 'district', 'bio', 'website',
            'industry_type_id', 'lisence_image', 'passport_image', 'id_card_image',
            'comapny_certificate_image', 'is_profile_compeleted',
            'otp_code', 'otp_expiry', 'is_otp_verified',
        ];

        Schema::table('admins', function (Blueprint $table) use ($cols) {
            foreach ($cols as $col) {
                if (Schema::hasColumn('admins', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
