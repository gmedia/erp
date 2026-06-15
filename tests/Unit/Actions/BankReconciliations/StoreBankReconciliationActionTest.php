<?php

use App\Actions\BankReconciliations\StoreBankReconciliationAction;
use App\Http\Requests\BankReconciliations\StoreBankReconciliationRequest;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class)->group('bank-reconciliations');

function makeStoreRequest(array $payload): StoreBankReconciliationRequest
{
    $request = StoreBankReconciliationRequest::create('/api/bank-reconciliations', 'POST', $payload);
    $request->setContainer(app())->setRedirector(app('redirect'));
    $request->validateResolved();

    return $request;
}

function storePayload(array $overrides = []): array
{
    $account = Account::factory()->create();
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);

    return array_merge([
        'account_id' => $account->id,
        'fiscal_year_id' => $fiscalYear->id,
        'reconciliation_date' => '2026-01-31',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'statement_balance' => 5000,
        'book_balance' => 5000,
        'reconciled_balance' => 5000,
        'difference' => 0,
        'status' => 'in_progress',
        'notes' => 'Initial reconciliation',
    ], $overrides);
}

test('store action creates bank reconciliation without items', function () {
    $user = User::factory()->create();
    actingAs($user);

    $request = makeStoreRequest(storePayload());
    $action = new StoreBankReconciliationAction;

    $reconciliation = $action->execute($request);

    expect($reconciliation)->toBeInstanceOf(BankReconciliation::class)
        ->and($reconciliation->notes)->toBe('Initial reconciliation')
        ->and($reconciliation->created_by)->toBe($user->id)
        ->and($reconciliation->items)->toHaveCount(0);
});

test('store action persists nested items', function () {
    $user = User::factory()->create();
    actingAs($user);

    $payload = storePayload([
        'items' => [
            ['transaction_date' => '2026-01-15', 'description' => 'Deposit', 'debit' => 0, 'credit' => 500, 'type' => 'deposit', 'is_reconciled' => true],
            ['transaction_date' => '2026-01-20', 'description' => 'Charge', 'debit' => 50, 'credit' => 0, 'type' => 'bank_charge', 'is_reconciled' => false],
        ],
    ]);

    $reconciliation = (new StoreBankReconciliationAction)->execute(makeStoreRequest($payload));

    expect($reconciliation->items)->toHaveCount(2);
});

test('store action defaults difference when not provided', function () {
    $user = User::factory()->create();
    actingAs($user);

    $payload = storePayload([
        'statement_balance' => 7000,
        'book_balance' => 6000,
    ]);
    unset($payload['difference'], $payload['reconciled_balance']);

    $reconciliation = (new StoreBankReconciliationAction)->execute(makeStoreRequest($payload));

    expect((float) $reconciliation->difference)->toBe(1000.0);
});

test('store action rolls back on failure', function () {
    $user = User::factory()->create();
    actingAs($user);

    $countBefore = BankReconciliation::count();

    $payload = storePayload([
        'items' => [
            ['transaction_date' => '2026-01-15', 'description' => 'OK', 'debit' => 0, 'credit' => 500, 'type' => 'deposit', 'is_reconciled' => true],
        ],
    ]);
    $request = makeStoreRequest($payload);

    DB::shouldReceive('transaction')->once()->andThrow(new RuntimeException('boom'));

    expect(fn () => (new StoreBankReconciliationAction)->execute($request))
        ->toThrow(RuntimeException::class, 'boom');

    expect(BankReconciliation::count())->toBe($countBefore);
});
