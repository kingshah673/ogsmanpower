<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncSuperAdminPermissions extends Command
{
    protected $signature = 'admin:sync-permissions
                            {--email=ogs.consultant@gmail.com : Admin email to assign superadmin when --all is not used}
                            {--all : Assign superadmin role to every admin panel user}';

    protected $description = 'Ensure menu-setting permissions exist and grant superadmin full admin-guard access';

    public function handle(): int
    {
        $guard = 'admin';

        $role = Role::firstOrCreate(
            ['name' => 'superadmin', 'guard_name' => $guard]
        );

        $menuPermissions = [
            'menu-setting.index',
            'menu-setting.create',
            'menu-setting.update',
            'menu-setting.delete',
        ];

        foreach ($menuPermissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => $guard],
                ['group_name' => 'menu-setting']
            );
        }

        $roleCrudGroups = [
            'agency' => ['agency.view', 'agency.create', 'agency.update', 'agency.delete'],
            'agent' => ['agent.view', 'agent.create', 'agent.update', 'agent.delete'],
            'broker' => ['broker.view', 'broker.create', 'broker.update', 'broker.delete'],
        ];

        foreach ($roleCrudGroups as $group => $names) {
            foreach ($names as $name) {
                Permission::firstOrCreate(
                    ['name' => $name, 'guard_name' => $guard],
                    ['group_name' => $group]
                );
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $allPermissions = Permission::where('guard_name', $guard)->pluck('name')->all();
        $role->syncPermissions($allPermissions);

        $this->info('Synced '.count($allPermissions).' permissions to superadmin.');

        if ($this->option('all')) {
            $count = 0;
            Admin::query()->each(function (Admin $admin) use ($role, &$count) {
                $admin->syncRoles([$role->name]);
                $this->line("Assigned superadmin to: {$admin->email}");
                $count++;
            });
            $this->info("Updated {$count} admin user(s).");
        } else {
            $email = (string) $this->option('email');
            $admin = Admin::where('email', $email)->first();

            if (! $admin) {
                $this->error("Admin not found: {$email}");

                return self::FAILURE;
            }

            $admin->syncRoles([$role->name]);
            $this->info("Assigned superadmin to: {$email}");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget('menu_lists');

        $this->info('Done. Log out of admin and log back in, then open Settings → Menu Settings.');

        return self::SUCCESS;
    }
}
