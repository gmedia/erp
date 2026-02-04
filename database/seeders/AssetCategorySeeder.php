<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'code' => 'KND',
                'name' => 'Kendaraan',
                'useful_life_months_default' => 96,
            ],
            [
                'code' => 'IT',
                'name' => 'IT Equipment',
                'useful_life_months_default' => 48,
            ],
            [
                'code' => 'MSN',
                'name' => 'Mesin Produksi',
                'useful_life_months_default' => 120,
            ],
            [
                'code' => 'PRB',
                'name' => 'Perabotan Kantor',
                'useful_life_months_default' => 60,
            ],
        ];

        foreach ($categories as $category) {
            AssetCategory::updateOrCreate(['code' => $category['code']], $category);
        }
    }
}
