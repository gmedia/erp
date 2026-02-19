<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetMaintenanceSeeder extends Seeder
{
    public function run(): void
    {
        $assets = Asset::query()->orderBy('id')->get();
        if ($assets->isEmpty()) {
            return;
        }

        $supplierId = Supplier::query()->orderBy('id')->value('id');
        $adminId = User::query()->where('email', config('app.admin'))->value('id');

        foreach (range(1, 10) as $i) {
            AssetMaintenance::factory()->create([
                'asset_id' => $assets->random()->id,
                'supplier_id' => $supplierId,
                'created_by' => $adminId,
            ]);
        }
    }
}
