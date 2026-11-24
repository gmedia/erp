<?php

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('bank-reconciliations');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['bank_reconciliation', 'bank_reconciliation.create', 'bank_reconciliation.edit']);
    Sanctum::actingAs($this->user, ['*']);
});

function reconciliationWithOpenYear(array $overrides = []): BankReconciliation
{
    $fiscalYear = FiscalYear::factory()->create([
        'status' => 'open',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'name' => '2026',
    ]);

    return BankReconciliation::factory()->create(array_merge([
        'account_id' => Account::factory()->create(['is_active' => true])->id,
        'fiscal_year_id' => $fiscalYear->id,
        'difference' => 0,
        'status' => 'in_progress',
        'reconciliation_date' => '2026-01-31',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
    ], $overrides));
}

test('completing a reconciliation with an unmatched item lacking an account returns 422', function () {
    $reconciliation = reconciliationWithOpenYear();
    BankReconciliationItem::factory()->create([
        'bank_reconciliation_id' => $reconciliation->id,
        'is_reconciled' => false,
        'journal_entry_line_id' => null,
        'account_id' => null,
        'debit' => 200,
        'credit' => 0,
    ]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');

    expect($reconciliation->refresh()->status)->toBe('in_progress');
    expect($reconciliation->journal_entry_id)->toBeNull();
    expect($reconciliation->items()->count())->toBe(1);
});

test('completing an already completed reconciliation returns 422', function () {
    $reconciliation = reconciliationWithOpenYear(['status' => 'completed', 'completed_at' => now()]);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('the reconciliation difference is calculated by the server, not taken from the client', function () {
    $reconciliation = reconciliationWithOpenYear([
        'statement_balance' => 5000,
        'book_balance' => 5000,
        'difference' => 0,
    ]);

    $payload = [
        'account_id' => $reconciliation->account_id,
        'fiscal_year_id' => $reconciliation->fiscal_year_id,
        'reconciliation_date' => '2026-01-31',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'statement_balance' => 5000,
        'book_balance' => 5000,
        'difference' => 0,
        'items' => [
            ['transaction_date' => '2026-01-15', 'description' => 'Deposit', 'debit' => 0, 'credit' => 500, 'type' => 'deposit', 'is_reconciled' => true],
        ],
    ];

    putJson("/api/bank-reconciliations/{$reconciliation->id}", $payload)->assertOk();

    // Item tercatat cocok (+500) sehingga selisih server = 5000 - (5000 + 500) = -500.
    assertDatabaseHas('bank_reconciliations', ['id' => $reconciliation->id, 'difference' => -500]);
});
