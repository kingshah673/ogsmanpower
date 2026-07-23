<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Roles that exist on localhost but were manually created (not seeded) so are missing on production.
        // guard_name is 'admin' — all admin-panel roles live under this guard.
        $rolesToAdd = [
            'agent',
            'third party workforce supply',
            'recruitment agency',
            'hr consultancy',
            'third party contracting small establishment',
            'domestic worker istaqdam offices',
        ];

        foreach ($rolesToAdd as $name) {
            DB::table('roles')->insertOrIgnore([
                'name'       => $name,
                'guard_name' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Roles that should have email OTP assigned (otp_method id=1 = email).
        $withEmailOtp = [
            'superadmin',                        // exists on production but missing the OTP assignment
            'agent',
            'third party workforce supply',
            'recruitment agency',
            'domestic worker istaqdam offices',
        ];

        $emailMethodId = DB::table('otp_methods')->where('name', 'email')->value('id');
        if (! $emailMethodId) {
            return; // otp_methods table not seeded — skip
        }

        foreach ($withEmailOtp as $roleName) {
            $roleId = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'admin')
                ->value('id');

            if (! $roleId) {
                continue;
            }

            $alreadyLinked = DB::table('role_has_otp_methods')
                ->where('role_id', $roleId)
                ->where('otp_method_id', $emailMethodId)
                ->exists();

            if (! $alreadyLinked) {
                DB::table('role_has_otp_methods')->insert([
                    'role_id'       => $roleId,
                    'otp_method_id' => $emailMethodId,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = [
            'agent',
            'third party workforce supply',
            'recruitment agency',
            'hr consultancy',
            'third party contracting small establishment',
            'domestic worker istaqdam offices',
        ];

        $roleIds = DB::table('roles')
            ->whereIn('name', $names)
            ->where('guard_name', 'admin')
            ->pluck('id');

        DB::table('role_has_otp_methods')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->delete();
    }
};
