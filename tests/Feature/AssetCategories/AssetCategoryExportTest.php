<?php

use App\Exports\AssetCategoryExport;
use App\Models\AssetCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('asset-categories');

describe('AssetCategoryExport', function () {

    test('query applies search filter by name', function () {
        AssetCategory::factory()->create(['name' => 'UniqueACEngineering']);
        AssetCategory::factory()->create(['name' => 'OtherACSale']);

        $export = new AssetCategoryExport(['search' => 'UniqueAC']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->name)->toBe('UniqueACEngineering');
    });

    test('query applies search filter by code', function () {
        AssetCategory::factory()->create(['code' => 'CODE-UNIQUE-1']);
        AssetCategory::factory()->create(['code' => 'OTHER']);

        $export = new AssetCategoryExport(['search' => 'CODE-UNIQUE']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->code)->toBe('CODE-UNIQUE-1');
    });

    test('map function returns correct data', function () {
        $category = AssetCategory::factory()->make([
            'id' => 1,
            'code' => 'AC001',
            'name' => 'Test Category',
            'useful_life_months_default' => 48,
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new AssetCategoryExport([]);
        $mapped = $export->map($category);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('AC001');
        expect($mapped[2])->toBe('Test Category');
        expect($mapped[3])->toBe(48);
    });

    test('headings returns correct columns', function () {
        $export = new AssetCategoryExport([]);
        
        expect($export->headings())->toContain('ID', 'Code', 'Name', 'Default Useful Life (Months)', 'Created At');
    });
});
