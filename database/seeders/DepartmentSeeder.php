<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'HR', 'Engineering', 'Sales', 'Marketing', 'Finance',
            'Operations', 'Customer Support', 'Product', 'Design', 'Legal'
        ];

        foreach ($departments as $name) {
            Department::firstOrCreate(['name' => $name]);
        }
    }
}
