<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Insert Seeker and Employer roles (skip if already exist)
        $seekerId = DB::table('roles')
            ->where('name', 'Seeker')
            ->value('id');

        if (!$seekerId) {
            $seekerId = DB::table('roles')->insertGetId([
                'name'       => 'Seeker',
                'guard_name' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $employerId = DB::table('roles')
            ->where('name', 'Employer')
            ->value('id');

        if (!$employerId) {
            $employerId = DB::table('roles')->insertGetId([
                'name'       => 'Employer',
                'guard_name' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Assign email (id=1) and whatsapp (id=3) OTP methods to both
        foreach ([$seekerId, $employerId] as $roleId) {
            foreach ([1 => true, 3 => false] as $methodId => $isDefault) {
                $exists = DB::table('role_has_otp_methods')
                    ->where('role_id', $roleId)
                    ->where('otp_method_id', $methodId)
                    ->exists();

                if (!$exists) {
                    DB::table('role_has_otp_methods')->insert([
                        'role_id'       => $roleId,
                        'otp_method_id' => $methodId,
                        'is_default'    => $isDefault,
                        'priority'      => $isDefault ? 1 : 0,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $roles = DB::table('roles')
            ->whereIn('name', ['Seeker', 'Employer'])
            ->pluck('id');

        DB::table('role_has_otp_methods')
            ->whereIn('role_id', $roles)
            ->delete();

        DB::table('roles')
            ->whereIn('name', ['Seeker', 'Employer'])
            ->delete();
    }
};
