<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AdminSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    protected array $permissions = [
        'dashboard.view',
        'campaigns.view',
        'campaigns.create',
        'campaigns.update',
        'campaigns.delete',
        'questions.manage',
        'players.view',
        'players.manage',
        'analytics.view',
        'analytics.manage',
        'settings.manage',
        'website.manage',
        'seo.manage',
        'cms.manage',
        'banners.manage',
        'notifications.manage',
        'backups.manage',
        'admins.view',
        'admins.manage',
        'roles.manage',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->permissions as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        $superAdminRole = Role::findOrCreate('super-admin', 'admin');
        $superAdminRole->syncPermissions(Permission::where('guard_name', 'admin')->get());

        $admin = Admin::query()->updateOrCreate(
            ['email' => 'admin@onamdare.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'is_active' => true,
            ]
        );

        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole($superAdminRole);
        }
    }
}
