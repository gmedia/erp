<?php

use App\Exports\InventoryStocktakeExport;
use App\Models\InventoryStocktake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('inventory-stocktakes');

describe('InventoryStocktakeExport', function () {
    test('it exports inventory stocktakes and returns file url', function () {
        Excel::fake();
        Storage::fake('public');
        InventoryStocktake::factory()->create();

        $user = createTestUserWithPermissions(['inventory_stocktake']);
        Sanctum::actingAs($user, ['*']);

        $response = postJson('/api/inventory-stocktakes/export', [
            'status' => 'draft',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);

        $filename = $response->json('filename');
        expect($filename)->toContain('inventory_stocktakes_export_');
        Excel::assertStored('exports/' . $filename, 'public');
    });

    test('query applies search filter', function () {
        InventoryStocktake::factory()->create(['stocktake_number' => 'SO-UNIQUE-001', 'status' => 'draft']);
        InventoryStocktake::factory()->create(['stocktake_number' => 'SO-OTHER-001', 'status' => 'draft']);

        $export = new InventoryStocktakeExport(['search' => 'SO-UNIQUE']);
        $results = $export->query()->get();

        expect($results)->toHaveCount(1)
            ->and($results->first()->stocktake_number)->toBe('SO-UNIQUE-001');
    });

    test('map function returns correct data', function () {
        $stocktake = InventoryStocktake::factory()->make([
            'id' => 1,
            'stocktake_number' => 'SO-TEST-0001',
            'created_at' => '2023-01-01 12:00:00',
        ]);

        $export = new InventoryStocktakeExport([]);
        $mapped = $export->map($stocktake);

        expect($mapped)->toBeArray();
        expect($mapped[0])->toBe(1);
        expect($mapped[1])->toBe('SO-TEST-0001');
    });

    test('headings returns correct columns', function () {
        $export = new InventoryStocktakeExport([]);

        expect($export->headings())->toContain(
            'ID',
            'Stocktake Number',
            'Warehouse',
            'Stocktake Date',
            'Status',
            'Product Category',
            'Completed At',
            'Created At',
        );
    });
});
