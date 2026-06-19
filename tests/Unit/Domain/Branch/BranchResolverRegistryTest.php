<?php

use App\Domain\Branch\BranchResolutionStrategy;
use App\Domain\Branch\BranchResolverRegistry;
use App\Models\ApPayment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\GoodsReceipt;
use App\Models\RecurringJournal;
use App\Models\StockAdjustment;
use App\Models\SupplierReturn;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('branch-resolver');

beforeEach(function () {
    $this->registry = new BranchResolverRegistry;
});

it('resolves a direct-branch source via its own branch_id', function () {
    $payment = ApPayment::factory()->create();

    expect($this->registry->resolve($payment))->toBe($payment->branch_id);
});

it('resolves a warehouse-based source via warehouse branch_id', function () {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $receipt = GoodsReceipt::factory()->create(['warehouse_id' => $warehouse->id]);

    expect($this->registry->resolve($receipt->fresh()))->toBe($branch->id);
});

it('returns null when a warehouse-based source has no branch', function () {
    $warehouse = Warehouse::factory()->create(['branch_id' => null]);
    $adjustment = StockAdjustment::factory()->create(['warehouse_id' => $warehouse->id]);

    expect($this->registry->resolve($adjustment->fresh()))->toBeNull();
});

it('resolves SupplierReturn via its warehouse branch', function () {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $return = SupplierReturn::factory()->create(['warehouse_id' => $warehouse->id]);

    expect($this->registry->resolve($return->fresh()))->toBe($branch->id);
});

it('throws for an unregistered type instead of silently returning null', function () {
    $customer = Customer::factory()->create();

    expect(fn () => $this->registry->resolve($customer))
        ->toThrow(InvalidArgumentException::class);
});

it('returns null for a registered None-strategy type', function () {
    $recurring = RecurringJournal::factory()->create();

    expect($this->registry->resolve($recurring))->toBeNull();
});

it('excludes None-strategy types from branch-bearing types', function () {
    $types = $this->registry->branchBearingTypes();

    expect($types)->not->toContain(RecurringJournal::class)
        ->and($this->registry->isRegistered(RecurringJournal::class))->toBeTrue();
});

it('reports registration status per type', function () {
    expect($this->registry->isRegistered(ApPayment::class))->toBeTrue()
        ->and($this->registry->isRegistered(Customer::class))->toBeFalse();
});

it('lists only branch-bearing types', function () {
    $types = $this->registry->branchBearingTypes();

    expect($types)->toContain(ApPayment::class)
        ->and($types)->toContain(GoodsReceipt::class)
        ->and($types)->not->toContain(Customer::class);
});

it('returns the warehouse relation only for warehouse-based types', function () {
    expect($this->registry->relationsFor(GoodsReceipt::class))->toBe(['warehouse'])
        ->and($this->registry->relationsFor(ApPayment::class))->toBe([]);
});

it('maps strategies to the expected enum cases', function () {
    expect($this->registry->relationsFor(StockAdjustment::class))->toBe(['warehouse']);

    expect(BranchResolutionStrategy::cases())
        ->toContain(BranchResolutionStrategy::Direct)
        ->toContain(BranchResolutionStrategy::Warehouse)
        ->toContain(BranchResolutionStrategy::None);
});
