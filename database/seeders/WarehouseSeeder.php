<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            'Main Warehouse',
            'Transit Warehouse',
            'Return Warehouse',
            'Production Warehouse',
        ];

        foreach ($warehouses as $name) {
            Warehouse::updateOrCreate(['name' => $name]);
        }
    }
}
