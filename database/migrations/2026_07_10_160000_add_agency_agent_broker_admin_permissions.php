<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('roles')) {
            return;
        }

        $now = now();
        $perms = [
            ['name' => 'agency.view', 'group_name' => 'agency'],
            ['name' => 'agency.create', 'group_name' => 'agency'],
            ['name' => 'agency.update', 'group_name' => 'agency'],
            ['name' => 'agency.delete', 'group_name' => 'agency'],
            ['name' => 'agent.view', 'group_name' => 'agent'],
            ['name' => 'agent.create', 'group_name' => 'agent'],
            ['name' => 'agent.update', 'group_name' => 'agent'],
            ['name' => 'agent.delete', 'group_name' => 'agent'],
            ['name' => 'broker.view', 'group_name' => 'broker'],
            ['name' => 'broker.create', 'group_name' => 'broker'],
            ['name' => 'broker.update', 'group_name' => 'broker'],
            ['name' => 'broker.delete', 'group_name' => 'broker'],
        ];

        $superadmin = DB::table('roles')
            ->where('name', 'superadmin')
            ->where('guard_name', 'admin')
            ->first();

        foreach ($perms as $perm) {
            $existing = DB::table('permissions')
                ->where('name', $perm['name'])
                ->where('guard_name', 'admin')
                ->first();

            if ($existing) {
                $permissionId = $existing->id;
            } else {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $perm['name'],
                    'group_name' => $perm['group_name'],
                    'guard_name' => 'admin',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($superadmin) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $superadmin->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $names = [
            'agency.view', 'agency.create', 'agency.update', 'agency.delete',
            'agent.view', 'agent.create', 'agent.update', 'agent.delete',
            'broker.view', 'broker.create', 'broker.update', 'broker.delete',
        ];

        $ids = DB::table('permissions')
            ->whereIn('name', $names)
            ->where('guard_name', 'admin')
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
