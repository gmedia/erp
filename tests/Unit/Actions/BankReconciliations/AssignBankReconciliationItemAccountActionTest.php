<?php

use App\Actions\BankReconciliations\AssignBankReconciliationItemAccountAction;
use App\Http\Requests\BankReconciliations\AssignBankReconciliationItemAccountRequest;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('bank-reconciliations');

function makeAssignRequest(array $payload): AssignBankReconciliationItemAccountRequest
{
    $request = AssignBankReconciliationItemAccountRequest::create('/x', 'PUT', $payload);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    return $request;
}

test('assign action sets account on item', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'account_id' => null,
    ]);
    $account = Account::factory()->create();

    $request = makeAssignRequest(['account_id' => $account->id]);

    $updated = (new AssignBankReconciliationItemAccountAction)->execute($request, $reconciliation, $item->id);

    expect($updated->account_id)->toBe($account->id);
});

test('assign action overwrites existing account', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $oldAccount = Account::factory()->create();
    $newAccount = Account::factory()->create();
    $item = BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'account_id' => $oldAccount->id,
    ]);

    $request = makeAssignRequest(['account_id' => $newAccount->id]);

    $updated = (new AssignBankReconciliationItemAccountAction)->execute($request, $reconciliation, $item->id);

    expect($updated->account_id)->toBe($newAccount->id)
        ->and($updated->account_id)->not->toBe($oldAccount->id);
});

test('assign action throws when item belongs to different reconciliation', function () {
    $reconciliationA = BankReconciliation::factory()->create();
    $reconciliationB = BankReconciliation::factory()->create();
    $item = BankReconciliationItem::factory()->create(['bank_reconciliation_id' => $reconciliationA->id]);
    $account = Account::factory()->create();

    $request = makeAssignRequest(['account_id' => $account->id]);

    expect(fn () => (new AssignBankReconciliationItemAccountAction)->execute($request, $reconciliationB, $item->id))
        ->toThrow(ModelNotFoundException::class);
});
