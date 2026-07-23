<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('agencies', function (Blueprint $table) {

            // 👤 OWNER KYC
            $table->date('dob')->nullable();
            $table->string('id_type')->nullable();
            $table->string('id_number')->nullable();
            $table->date('cnic_expiry')->nullable();
            $table->date('passport_expiry')->nullable();

            // 🏢 COMPANY EXTRA
            $table->string('registration_number')->nullable();
            $table->date('license_expiry')->nullable();

            // 🌍 LOCATION
            $table->unsignedBigInteger('country_id')->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();

            // 🌐 MULTI COUNTRY
            $table->json('working_countries')->nullable();

            // 📜 AGREEMENT
            $table->boolean('agreement_accepted')->default(0);

            // 📊 STATUS (if not exists)
            
        });
    }

    public function down()
    {
        Schema::table('agencies', function (Blueprint $table) {

            $table->dropColumn([
                'dob',
                'id_type',
                'id_number',
                'cnic_expiry',
                'passport_expiry',
                'registration_number',
                'license_expiry',
                'country_id',
                'state_id',
                'city_id',
                'working_countries',
                'agreement_accepted',
                
            ]);
        });
    }
};