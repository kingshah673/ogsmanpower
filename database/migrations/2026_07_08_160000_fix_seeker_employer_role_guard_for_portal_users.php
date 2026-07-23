<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach (['Seeker', 'Employer'] as $roleName) {
            $hasUserGuardRole = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'user')
                ->exists();

            if ($hasUserGuardRole) {
                continue;
            }

            $adminRole = DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'admin')
                ->first();

            if ($adminRole) {
                DB::table('roles')
                    ->where('id', $adminRole->id)
                    ->update([
                        'guard_name' => 'user',
                        'updated_at' => $now,
                    ]);

                continue;
            }

            $roleId = DB::table('roles')->insertGetId([
                'name' => $roleName,
                'guard_name' => 'user',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ([1 => true, 3 => false] as $methodId => $isDefault) {
                $exists = DB::table('role_has_otp_methods')
                    ->where('role_id', $roleId)
                    ->where('otp_method_id', $methodId)
                    ->exists();

                if (! $exists) {
                    DB::table('role_has_otp_methods')->insert([
                        'role_id' => $roleId,
                        'otp_method_id' => $methodId,
                        'is_default' => $isDefault,
                        'priority' => $isDefault ? 1 : 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        $now = now();

        foreach (['Seeker', 'Employer'] as $roleName) {
            DB::table('roles')
                ->where('name', $roleName)
                ->where('guard_name', 'user')
                ->update([
                    'guard_name' => 'admin',
                    'updated_at' => $now,
                ]);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
};
