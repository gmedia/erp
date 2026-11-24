<?php

use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('demo-integrity');

/**
 * Keputusan #357: penyusutan aset demo harus berasal dari run depresiasi yang
 * diposting, termasuk kendaraan FA-000013..15 dari VehicleLoanSampleDataSeeder.
 */
test('demo assets FA-000013..15 get depreciation lines from posted runs after seeding', function () {
    test()->seed();

    $vehicleAssets = Asset::query()
        ->whereIn('asset_code', ['FA-000013', 'FA-000014', 'FA-000015'])
        ->get();

    expect($vehicleAssets)->toHaveCount(3);

    foreach ($vehicleAssets as $asset) {
        $lines = AssetDepreciationLine::query()
            ->join('asset_depreciation_runs', 'asset_depreciation_runs.id', '=', 'asset_depreciation_lines.asset_depreciation_run_id')
            ->where('asset_depreciation_lines.asset_id', $asset->id)
            ->where('asset_depreciation_runs.status', 'posted')
            ->count();

        expect($lines)->toBeGreaterThan(0, "{$asset->asset_code} harus punya baris penyusutan dari run terposting");
    }

    expect(AssetDepreciationRun::query()->where('status', 'posted')->exists())->toBeTrue();
});
