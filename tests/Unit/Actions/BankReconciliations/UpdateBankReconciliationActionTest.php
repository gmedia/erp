<?php

use App\Actions\BankReconciliations\UpdateBankReconciliationAction;
use App\Http\Requests\BankReconciliations\UpdateBankReconciliationRequest;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('bank-reconciliations');

function makeUpdateRequest(BankReconciliation $reconciliation, array $overrides): UpdateBankReconciliationRequest
{
    $payload = array_merge([
        'account_id' => $reconciliation->account_id,
        'fiscal_year_id' => $reconciliation->fiscal_year_id,
        'reconciliation_date' => '2026-01-31',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'statement_balance' => (float) $reconciliation->statement_balance,
        'book_balance' => (float) $reconciliation->book_balance,
        'reconciled_balance' => (float) $reconciliation->reconciled_balance,
        'difference' => (float) $reconciliation->difference,
        'status' => $reconciliation->status,
        'notes' => $reconciliation->notes,
    ], $overrides);

    $request = UpdateBankReconciliationRequest::create("/api/bank-reconciliations/{$reconciliation->id}", 'PUT', $payload);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    return $request;
}

test('update action updates scalar fields without touching items', function () {
    $reconciliation = BankReconciliation::factory()->create(['notes' => 'old']);
    BankReconciliationItem::factory()->count(2)->create(['bank_reconciliation_id' => $reconciliation->id]);

    $request = makeUpdateRequest($reconciliation, ['notes' => 'new']);

    $updated = (new UpdateBankReconciliationAction)->execute($request, $reconciliation);

    expect($updated->notes)->toBe('new')
        ->and($updated->items)->toHaveCount(2);
});

test('update action replaces items when items array provided', function () {
    $reconciliation = BankReconciliation::factory()->create();
    BankReconciliationItem::factory()->count(3)->create(['bank_reconciliation_id' => $reconciliation->id]);

    $request = makeUpdateRequest($reconciliation, [
        'items' => [
            ['transaction_date' => '2026-02-01', 'description' => 'New A', 'debit' => 100, 'credit' => 0, 'type' => 'bank_charge', 'is_reconciled' => false],
            ['transaction_date' => '2026-02-02', 'description' => 'New B', 'debit' => 0, 'credit' => 200, 'type' => 'deposit', 'is_reconciled' => true],
        ],
    ]);

    $updated = (new UpdateBankReconciliationAction)->execute($request, $reconciliation);

    expect($updated->items)->toHaveCount(2)
        ->and($updated->items->pluck('description')->all())->toEqual(['New A', 'New B']);
});

test('update action skips item replacement when items omitted', function () {
    $reconciliation = BankReconciliation::factory()->create();
    BankReconciliationItem::factory()->count(4)->create(['bank_reconciliation_id' => $reconciliation->id]);

    $request = makeUpdateRequest($reconciliation, ['notes' => 'just notes']);

    (new UpdateBankReconciliationAction)->execute($request, $reconciliation);

    expect($reconciliation->fresh()->items)->toHaveCount(4);
});

test('update action wraps work in transaction (rolls back on failure)', function () {
    $reconciliation = BankReconciliation::factory()->create(['notes' => 'before']);
    BankReconciliationItem::factory()->count(2)->create(['bank_reconciliation_id' => $reconciliation->id]);

    $request = makeUpdateRequest($reconciliation, ['notes' => 'mid-flight']);

    DB::shouldReceive('transaction')->once()->andThrow(new RuntimeException('boom'));

    expect(fn () => (new UpdateBankReconciliationAction)->execute($request, $reconciliation))
        ->toThrow(RuntimeException::class, 'boom');

    $reconciliation->refresh();
    expect($reconciliation->notes)->toBe('before')
        ->and($reconciliation->items)->toHaveCount(2);
});
