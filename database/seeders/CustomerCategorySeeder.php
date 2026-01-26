<?php

namespace Database\Seeders;

use App\Models\CustomerCategory;
use Illuminate\Database\Seeder;

class CustomerCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Retail', 'Wholesale', 'Government', 'Corporate', 
            'Reseller', 'VIP', 'Online', 'Walk-in'
        ];

        foreach ($categories as $name) {
            CustomerCategory::updateOrCreate(['name' => $name]);
        }
    }
}
