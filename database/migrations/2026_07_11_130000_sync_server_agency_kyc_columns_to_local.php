<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Server agencies table has KYC/registration columns that local is missing
 * (original create/KYC migrations ran against a different schema history).
 * Safe on both envs: only adds if missing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agencies')) {
            return;
        }

        Schema::table('agencies', function (Blueprint $table) {
            if (! Schema::hasColumn('agencies', 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'owner_name')) {
                $table->string('owner_name')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'email')) {
                $table->string('email')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'city')) {
                $table->string('city')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'status')) {
                $table->string('status')->nullable()->default('pending');
            }
            if (! Schema::hasColumn('agencies', 'dob')) {
                $table->date('dob')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'id_type')) {
                $table->string('id_type')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'id_number')) {
                $table->string('id_number')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'cnic_expiry')) {
                $table->date('cnic_expiry')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'passport_expiry')) {
                $table->date('passport_expiry')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'registration_number')) {
                $table->string('registration_number')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'license_expiry')) {
                $table->date('license_expiry')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'country_id')) {
                $table->unsignedBigInteger('country_id')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'state_id')) {
                $table->unsignedBigInteger('state_id')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'working_countries')) {
                $table->json('working_countries')->nullable();
            }
            if (! Schema::hasColumn('agencies', 'agreement_accepted')) {
                $table->boolean('agreement_accepted')->default(false);
            }
        });
    }

    public function down(): void
    {
        // Keep columns — production already uses them.
    }
};
