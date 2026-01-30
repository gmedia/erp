<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Seed default test user
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Seed admin user for Playwright test
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            BranchSeeder::class,
            DepartmentSeeder::class,
            PositionSeeder::class,
            ProductCategorySeeder::class,
            UnitSeeder::class,
            CoaSeeder::class,
        ]);

        Employee::updateOrCreate([
            'email' => 'admin@admin.com',
            'user_id' => $admin->id,
        ], [
            'name' => 'Admin User',
            'phone' => '1234567890',
            'department_id' => 1,
            'position_id' => 1,
            'branch_id' => 1,
            'salary' => 100000,
            'hire_date' => now(),
        ]);

        $this->call([
            PermissionSeeder::class,
            MenuSeeder::class,
            CustomerCategorySeeder::class,
            SupplierCategorySeeder::class,
            ProductSampleDataSeeder::class,
        ]);
    }
}
