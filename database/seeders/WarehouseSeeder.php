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
            ->where('name', DemoMasterData::BRANCH_HEAD_OFFICE)
            ->value('id') ?? Branch::query()->value('id');

        $warehouses = [
            ['code' => DemoMasterData::WAREHOUSE_MAIN_CODE, 'name' => DemoMasterData::WAREHOUSE_MAIN_NAME],
            ['code' => DemoMasterData::WAREHOUSE_TRANSIT_CODE, 'name' => DemoMasterData::WAREHOUSE_TRANSIT_NAME],
            ['code' => DemoMasterData::WAREHOUSE_RETURN_CODE, 'name' => DemoMasterData::WAREHOUSE_RETURN_NAME],
            ['code' => DemoMasterData::WAREHOUSE_PRODUCTION_CODE, 'name' => DemoMasterData::WAREHOUSE_PRODUCTION_NAME],
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
