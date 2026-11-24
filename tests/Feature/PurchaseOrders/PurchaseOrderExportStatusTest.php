<?php

use App\Exports\PurchaseOrderExport;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('purchase-orders');

test('export menyaring PO dengan daftar status', function () {
    PurchaseOrder::factory()->create(['status' => 'draft']);
    $confirmed = PurchaseOrder::factory()->create(['status' => 'confirmed']);
    $partial = PurchaseOrder::factory()->create(['status' => 'partially_received']);

    $ids = fn (array $status): array => (new PurchaseOrderExport(['status' => $status]))->query()
        ->pluck('id')->sort()->values()->all();

    expect($ids(['confirmed']))->toBe([$confirmed->id])
        ->and($ids(['confirmed', 'partially_received']))->toBe([$confirmed->id, $partial->id]);
});
