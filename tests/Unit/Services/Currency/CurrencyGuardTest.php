<?php

use App\Exceptions\Currency\MixedCurrencyException;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Currency\CurrencyGuard;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('currency-guard');

beforeEach(function (): void {
    $this->guard = new CurrencyGuard;
});

test('assertHomogeneousQuery passes when all rows share one currency', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();

    PurchaseOrder::factory()->count(3)->create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'currency' => 'IDR',
    ]);

    /** @var Builder $query */
    $query = DB::table('purchase_orders');

    $this->guard->assertHomogeneousQuery($query, 'test');

    expect(true)->toBeTrue();
});

test('assertHomogeneousQuery throws when rows mix currencies', function (): void {
    $supplier = Supplier::factory()->create();
    $warehouse = Warehouse::factory()->create();

    PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'currency' => 'IDR',
    ]);
    PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'warehouse_id' => $warehouse->id,
        'currency' => 'USD',
    ]);

    /** @var Builder $query */
    $query = DB::table('purchase_orders');

    expect(fn () => $this->guard->assertHomogeneousQuery($query, 'aging-dashboard'))
        ->toThrow(MixedCurrencyException::class);
});

test('assertHomogeneousQuery passes when query is empty', function (): void {
    /** @var Builder $query */
    $query = DB::table('purchase_orders');

    $this->guard->assertHomogeneousQuery($query, 'empty-set');

    expect(true)->toBeTrue();
});

test('assertHomogeneousRows passes for single-currency array', function (): void {
    $rows = [
        ['currency' => 'IDR', 'amount' => 100],
        ['currency' => 'IDR', 'amount' => 200],
    ];

    $this->guard->assertHomogeneousRows($rows, 'collection');

    expect(true)->toBeTrue();
});

test('assertHomogeneousRows throws for mixed-currency array', function (): void {
    $rows = [
        ['currency' => 'IDR', 'amount' => 100],
        ['currency' => 'USD', 'amount' => 200],
    ];

    expect(fn () => $this->guard->assertHomogeneousRows($rows, 'aging-report'))
        ->toThrow(MixedCurrencyException::class);
});

test('assertHomogeneousRows ignores null and empty currency values', function (): void {
    $rows = [
        ['currency' => 'IDR', 'amount' => 100],
        ['currency' => null, 'amount' => 50],
        ['currency' => '', 'amount' => 25],
        ['currency' => 'IDR', 'amount' => 200],
    ];

    $this->guard->assertHomogeneousRows($rows, 'mixed-nulls');

    expect(true)->toBeTrue();
});

test('MixedCurrencyException returns 422 with currency error key', function (): void {
    $exception = new MixedCurrencyException('aging', ['IDR', 'USD']);
    $response = $exception->getResponse();

    expect($response->getStatusCode())->toBe(422);

    $payload = json_decode($response->getContent() ?: '', true);

    expect($payload['errors'])->toHaveKey('currency');
    expect($payload['message'])->toContain('aging');
    expect($payload['message'])->toContain('IDR');
    expect($payload['message'])->toContain('USD');
});
