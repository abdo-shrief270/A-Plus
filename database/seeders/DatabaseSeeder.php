<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Artisan::call('shield:install admin');
        Artisan::call('shield:generate --all --panel=admin');
        $this->command->info("Shield Installation is completed successfully");


        $superAdminRole = Role::where('name' , 'مدير النظام')->first();
        $adminRole = Role::create(['name' => 'المدير', 'guard_name' => 'web']);
        $dataEntryRole = Role::create(['name' => 'مدخل بيانات', 'guard_name' => 'web']);
        $salesRole = Role::create(['name' => 'مبيعات', 'guard_name' => 'web']);
        $this->command->info("Roles Has Created successfully");

        $permissions = Permission::all();
        $superAdminRole->syncPermissions($permissions);
        $this->command->info("Permissions assigned to roles successfully");

        Admin::create([
            'name' => 'Abdo Shrief',
            'email' => 'abdo.shrief270@gmail.com',
            'password' => Hash::make('954816899'),
            'active' => true
        ]);

        Artisan::call('shield:super-admin --user=1 --panel=admin');
        $this->command->info("Admins Has Created successfully");

    }
}
