<?php

use App\Actions\BankReconciliations\AddBankReconciliationItemAction;
use App\Http\Requests\BankReconciliations\AddBankReconciliationItemRequest;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class)->group('bank-reconciliations');

function makeAddItemRequest(array $payload): AddBankReconciliationItemRequest
{
    $request = AddBankReconciliationItemRequest::create('/x', 'POST', $payload);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    return $request;
}

test('add item action persists item and recalculates balances', function () {
    $reconciliation = BankReconciliation::factory()->create([
        'statement_balance' => 10000,
        'book_balance' => 9500,
        'reconciled_balance' => 9500,
        'difference' => 500,
    ]);

    $request = makeAddItemRequest([
        'transaction_date' => '2026-01-15',
        'description' => 'Deposit',
        'debit' => 0,
        'credit' => 500,
        'type' => 'deposit',
        'is_reconciled' => true,
    ]);

    $item = (new AddBankReconciliationItemAction)->execute($request, $reconciliation);

    $reconciliation->refresh();

    expect($item)->toBeInstanceOf(BankReconciliationItem::class)
        ->and($item->description)->toBe('Deposit')
        ->and((float) $reconciliation->reconciled_balance)->toBe(10000.0)
        ->and((float) $reconciliation->difference)->toBe(0.0);
});

test('add item action does not touch balances for unreconciled item', function () {
    $reconciliation = BankReconciliation::factory()->create([
        'statement_balance' => 10000,
        'book_balance' => 10000,
        'reconciled_balance' => 10000,
        'difference' => 0,
    ]);

    $request = makeAddItemRequest([
        'transaction_date' => '2026-01-20',
        'description' => 'Pending charge',
        'debit' => 200,
        'credit' => 0,
        'type' => 'bank_charge',
        'is_reconciled' => false,
    ]);

    (new AddBankReconciliationItemAction)->execute($request, $reconciliation);

    $reconciliation->refresh();
    expect((float) $reconciliation->reconciled_balance)->toBe(10000.0)
        ->and((float) $reconciliation->difference)->toBe(0.0);
});

test('add item action rolls back item creation when transaction fails', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $countBefore = BankReconciliationItem::where('bank_reconciliation_id', $reconciliation->id)->count();

    $request = makeAddItemRequest([
        'transaction_date' => '2026-01-15',
        'description' => 'Deposit',
        'debit' => 0,
        'credit' => 500,
        'is_reconciled' => true,
    ]);

    DB::shouldReceive('transaction')->once()->andThrow(new RuntimeException('boom'));

    expect(fn () => (new AddBankReconciliationItemAction)->execute($request, $reconciliation))
        ->toThrow(RuntimeException::class, 'boom');

    expect(BankReconciliationItem::where('bank_reconciliation_id', $reconciliation->id)->count())
        ->toBe($countBefore);
});
