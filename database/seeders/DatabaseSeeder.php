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
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Seed admin user for Playwright test
        $admin = User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            DepartmentSeeder::class,
            PositionSeeder::class,
        ]);

        Employee::firstOrCreate([
            'user_id' => $admin->id,
            'name' => 'Admin User',
            'email' => 'admin@admin.com',
            'phone' => '1234567890',
            'department_id' => 1,
            'position_id' => 1,
            'salary' => 100000,
            'hire_date' => now(),
        ]);

        $this->call([
            PermissionSeeder::class,
            MenuSeeder::class,
        ]);
    }
}
