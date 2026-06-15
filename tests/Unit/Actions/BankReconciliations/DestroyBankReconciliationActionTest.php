<?php

use App\Actions\BankReconciliations\DestroyBankReconciliationAction;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('bank-reconciliations');

test('destroy action deletes reconciliation without items', function () {
    $reconciliation = BankReconciliation::factory()->create();

    (new DestroyBankReconciliationAction)->execute($reconciliation);

    expect(BankReconciliation::find($reconciliation->id))->toBeNull();
});

test('destroy action cascades to items', function () {
    $reconciliation = BankReconciliation::factory()->create();
    BankReconciliationItem::factory()->count(3)->create(['bank_reconciliation_id' => $reconciliation->id]);

    (new DestroyBankReconciliationAction)->execute($reconciliation);

    expect(BankReconciliation::find($reconciliation->id))->toBeNull()
        ->and(BankReconciliationItem::where('bank_reconciliation_id', $reconciliation->id)->count())->toBe(0);
});

test('destroy action rolls back when delete throws', function () {
    $reconciliation = BankReconciliation::factory()->create();
    BankReconciliationItem::factory()->count(2)->create(['bank_reconciliation_id' => $reconciliation->id]);

    DB::shouldReceive('transaction')->once()->andThrow(new RuntimeException('boom'));

    expect(fn () => (new DestroyBankReconciliationAction)->execute($reconciliation))
        ->toThrow(RuntimeException::class, 'boom');

    expect(BankReconciliation::find($reconciliation->id))->not->toBeNull()
        ->and(BankReconciliationItem::where('bank_reconciliation_id', $reconciliation->id)->count())->toBe(2);
});
