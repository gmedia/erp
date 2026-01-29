<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Electronics', 'description' => 'Electronic products and devices'],
            ['name' => 'Office Supplies', 'description' => 'Office equipment and supplies'],
            ['name' => 'Furniture', 'description' => 'Office and home furniture'],
            ['name' => 'Raw Materials - Wood', 'description' => 'Wood and timber materials'],
            ['name' => 'Raw Materials - Hardware', 'description' => 'Hardware components and fasteners'],
            ['name' => 'Finished Goods - Furniture', 'description' => 'Manufactured furniture products'],
            ['name' => 'Consulting Services', 'description' => 'Professional consulting services'],
            ['name' => 'Maintenance Services', 'description' => 'Maintenance and support services'],
            ['name' => 'Training Services', 'description' => 'Training and education services'],
            ['name' => 'Software & SaaS', 'description' => 'Software products and SaaS subscriptions'],
        ];

        foreach ($categories as $category) {
            DB::table('product_categories')->insert([
                'name' => $category['name'],
                'description' => $category['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
