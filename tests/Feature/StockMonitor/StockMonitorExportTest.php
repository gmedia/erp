<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('stock-monitor');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['stock_monitor']);
});

test('it can export stock monitor to xlsx', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->user)
        ->postJson('/api/stock-monitor/export')
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');

    expect($filename)->toStartWith('stock_monitor_2026-03-03_10-00-00_');
    expect($filename)->toEndWith('.xlsx');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});

test('it can export stock monitor to csv', function () {
    Carbon::setTestNow(Carbon::parse('2026-03-03 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $response = actingAs($this->user)
        ->postJson('/api/stock-monitor/export', ['format' => 'csv'])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    $filename = $response->json('filename');

    expect($filename)->toStartWith('stock_monitor_2026-03-03_10-00-00_');
    expect($filename)->toEndWith('.csv');

    Excel::assertStored('exports/' . $filename, 'public');
    Carbon::setTestNow();
});
