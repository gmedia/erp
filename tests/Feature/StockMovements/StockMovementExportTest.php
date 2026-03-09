<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('stock-movements');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['stock_movement']);
});

test('it can export stock movements to xlsx', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-01 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->user)
        ->postJson('/api/stock-movements/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('stock_movements_2026-02-01_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it can export stock movements to csv', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-01 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->user)
        ->postJson('/api/stock-movements/export', ['format' => 'csv'])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');
    expect($filename)->toStartWith('stock_movements_2026-02-01_10-00-00_');
    expect($filename)->toEndWith('.csv');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
