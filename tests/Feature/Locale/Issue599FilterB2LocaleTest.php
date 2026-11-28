<?php

use Illuminate\Support\Facades\File;

uses()->group('locale');

test('issue 599 sub-langkah B2 filter keys exist in both locales with english fallback text', function () {
    $en = issue599LocaleJson('en.json');
    $id = issue599LocaleJson('id.json');

    $expected = [
        'filter.select_movement_type' => ['Select movement type', 'Pilih jenis pergerakan'],
        'filter.example_10' => ['Example: 10', 'Contoh: 10'],
        'filter.filter_by_asset' => ['Filter by asset', 'Filter berdasarkan aset'],
        'filter.filter_by_type' => ['Filter by type', 'Filter berdasarkan jenis'],
        'filter.filter_by_status' => ['Filter by status', 'Filter berdasarkan status'],
        'filter.filter_by_supplier' => ['Filter by supplier', 'Filter berdasarkan pemasok'],
        'filter.all_statuses_title' => ['All Statuses', 'Semua Status'],
    ];

    foreach ($expected as $key => [$enText, $idText]) {
        expect(issue599LocaleLookup($en, $key))->toBe($enText, "en {$key}")
            ->and(issue599LocaleLookup($id, $key))->toBe($idText, "id {$key}");
    }
});

test('issue 599 sub-langkah B2 filter files no longer hardcode english placeholders', function () {
    $files = [
        'js/components/stock-transfers/StockTransferFilters.tsx',
        'js/components/stock-adjustments/StockAdjustmentFilters.tsx',
        'js/components/stock-movements/Filters.tsx',
        'js/components/stock-monitor/Filters.tsx',
        'js/components/asset-maintenances/AssetMaintenanceFilters.tsx',
        'js/components/asset-movements/AssetMovementFilters.tsx',
        'js/components/asset-stocktakes/AssetStocktakeFilters.tsx',
    ];

    foreach ($files as $file) {
        $source = File::get(resource_path($file));

        expect($source)->not->toMatch("/placeholder:\\s*'/", $file)
            ->and($source)->not->toMatch('/placeholder="/', $file)
            ->and($source)->not->toMatch("/createTextFilterField\\(\\s*'search',\\s*'Search'/", $file)
            ->and($source)->not->toMatch("/,\\s*'Select (status|type|warehouse|movement type)'/", $file)
            ->and($source)->not->toMatch("/,\\s*'(All [A-Za-z]+|Filter by [a-z]+)'/", $file);
    }
});
