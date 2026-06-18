<?php

use App\Models\ApPayment;
use App\Models\Branch;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\RecurringJournal;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\artisan;

uses(RefreshDatabase::class)->group('journal-entries');

it('backfills branch_id from a direct-branch source', function () {
    $payment = ApPayment::factory()->create();

    $entry = JournalEntry::factory()->create([
        'branch_id' => null,
        'source_type' => ApPayment::class,
        'source_id' => $payment->id,
    ]);

    artisan('journals:backfill-branch')->assertSuccessful();

    expect($entry->fresh()->branch_id)->toBe($payment->branch_id);
});

it('backfills branch_id via warehouse for warehouse-based sources', function () {
    $branch = Branch::factory()->create();
    $warehouse = Warehouse::factory()->create(['branch_id' => $branch->id]);
    $receipt = GoodsReceipt::factory()->create(['warehouse_id' => $warehouse->id]);

    $entry = JournalEntry::factory()->create([
        'branch_id' => null,
        'source_type' => GoodsReceipt::class,
        'source_id' => $receipt->id,
    ]);

    artisan('journals:backfill-branch')->assertSuccessful();

    expect($entry->fresh()->branch_id)->toBe($branch->id);
});

it('leaves branch_id null when the warehouse has no branch', function () {
    $warehouse = Warehouse::factory()->create(['branch_id' => null]);
    $receipt = GoodsReceipt::factory()->create(['warehouse_id' => $warehouse->id]);

    $entry = JournalEntry::factory()->create([
        'branch_id' => null,
        'source_type' => GoodsReceipt::class,
        'source_id' => $receipt->id,
    ]);

    artisan('journals:backfill-branch')->assertSuccessful();

    expect($entry->fresh()->branch_id)->toBeNull();
});

it('leaves branch_id null for no-branch sources', function () {
    $recurring = RecurringJournal::factory()->create();

    $entry = JournalEntry::factory()->create([
        'branch_id' => null,
        'source_type' => RecurringJournal::class,
        'source_id' => $recurring->id,
    ]);

    artisan('journals:backfill-branch')->assertSuccessful();

    expect($entry->fresh()->branch_id)->toBeNull();
});

it('writes nothing in dry-run mode', function () {
    $payment = ApPayment::factory()->create();

    $entry = JournalEntry::factory()->create([
        'branch_id' => null,
        'source_type' => ApPayment::class,
        'source_id' => $payment->id,
    ]);

    artisan('journals:backfill-branch', ['--dry-run' => true])->assertSuccessful();

    expect($entry->fresh()->branch_id)->toBeNull();
});

it('does not overwrite an already-populated branch_id', function () {
    $original = Branch::factory()->create();
    $payment = ApPayment::factory()->create();

    $entry = JournalEntry::factory()->create([
        'branch_id' => $original->id,
        'source_type' => ApPayment::class,
        'source_id' => $payment->id,
    ]);

    artisan('journals:backfill-branch')->assertSuccessful();

    expect($entry->fresh()->branch_id)->toBe($original->id);
});
