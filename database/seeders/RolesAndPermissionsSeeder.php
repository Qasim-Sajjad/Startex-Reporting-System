<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        // app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        Permission::create(['name' => 'create client']);
        Permission::create(['name' => 'view clients']);
        Permission::create(['name' => 'edit client']);
        Permission::create(['name' => 'delete client']);
        Permission::create(['name' => 'manage roles and permissions']);

        // Create roles and assign created permissions

        // Super Admin Role
        $superAdminRole = Role::create(['name' => 'Super Admin']);
        $superAdminRole->givePermissionTo(Permission::all());

        // Client Admin Role
        $clientAdminRole = Role::create(['name' => 'Client Admin']);
        $clientAdminRole->givePermissionTo([
            'create client',
            'view clients',
        ]);

        // Basic User Role
        Role::create(['name' => 'User']);
    }
}
