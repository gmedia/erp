<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $branchId = Branch::query()
            ->where('name', 'Head Office')
            ->value('id') ?? Branch::query()->value('id');

        $warehouses = [
            ['code' => 'MAIN', 'name' => 'Main Warehouse'],
            ['code' => 'TRN', 'name' => 'Transit Warehouse'],
            ['code' => 'RTN', 'name' => 'Return Warehouse'],
            ['code' => 'PRD', 'name' => 'Production Warehouse'],
        ];

        foreach ($warehouses as $item) {
            Warehouse::updateOrCreate([
                'branch_id' => $branchId,
                'code' => $item['code'],
            ], [
                'name' => $item['name'],
            ]);
        }
    }
}
