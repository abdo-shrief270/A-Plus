<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Install Shield if not already installed
        Artisan::call('shield:install admin');
        Artisan::call('shield:generate --all --panel=admin');
        $this->command->info('Shield permissions generated.');

        // Create Super Admin Role
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );

        // Create other default roles
        Role::firstOrCreate(['name' => 'مدير النظام', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'المدير', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'مدخل بيانات', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'مبيعات', 'guard_name' => 'web']);

        // Assign all permissions to super_admin
        $superAdminRole->syncPermissions(Permission::all());
        $this->command->info('Roles and permissions created.');

        // Create Super Admin User
        $admin = Admin::firstOrCreate(
            ['email' => 'admin@apls-edu.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'active' => true,
            ]
        );

        // Assign super_admin role
        $admin->assignRole('super_admin');

        // Make this user the Filament super admin
        Artisan::call('shield:super-admin', [
            '--user' => $admin->id,
            '--panel' => 'admin',
        ]);

        $this->command->info('✅ Super Admin created successfully!');
        $this->command->info('   Email: admin@apls-edu.com');
        $this->command->info('   Password: admin123');
    }
}
