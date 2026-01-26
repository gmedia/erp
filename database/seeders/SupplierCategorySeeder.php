<?php

namespace Database\Seeders;

use App\Models\SupplierCategory;
use Illuminate\Database\Seeder;

class SupplierCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics', 'Office Supplies', 'Furniture', 'IT Services', 
            'Logistics', 'Marketing', 'Maintenance', 'Utilities'
        ];

        foreach ($categories as $name) {
            SupplierCategory::updateOrCreate(['name' => $name]);
        }
    }
}
