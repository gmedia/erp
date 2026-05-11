<?php

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('bank-reconciliations');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['bank_reconciliation', 'bank_reconciliation.create', 'bank_reconciliation.edit', 'bank_reconciliation.delete']);
    Sanctum::actingAs($this->user, ['*']);
});

function bankReconciliationPayload(): array
{
    return [
        'account_id' => Account::factory()->create()->id,
        'fiscal_year_id' => FiscalYear::factory()->create(['status' => 'open'])->id,
        'reconciliation_date' => '2026-01-31',
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'statement_balance' => 5000,
        'book_balance' => 5000,
        'reconciled_balance' => 5000,
        'difference' => 0,
        'status' => 'in_progress',
        'notes' => 'January bank rec',
        'items' => [
            ['transaction_date' => '2026-01-15', 'description' => 'Deposit', 'debit' => 500, 'credit' => 0, 'type' => 'deposit', 'is_reconciled' => true],
        ],
    ];
}

test('it can list bank reconciliations', function () {
    BankReconciliation::factory()->count(3)->create();

    getJson('/api/bank-reconciliations')->assertOk()->assertJsonStructure(['data' => [['id', 'account', 'status', 'difference']], 'meta']);
});

test('it can create a bank reconciliation', function () {
    postJson('/api/bank-reconciliations', bankReconciliationPayload())
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'items']]);

    assertDatabaseHas('bank_reconciliations', ['notes' => 'January bank rec']);
    expect(BankReconciliation::first()->items)->toHaveCount(1);
});

test('it can show a bank reconciliation with items', function () {
    $reconciliation = BankReconciliation::factory()->create();
    BankReconciliationItem::factory()->create(['bank_reconciliation_id' => $reconciliation->id]);

    getJson("/api/bank-reconciliations/{$reconciliation->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $reconciliation->id)
        ->assertJsonStructure(['data' => ['items']]);
});

test('it can update a bank reconciliation', function () {
    $reconciliation = BankReconciliation::factory()->create(['notes' => 'Old notes']);
    $payload = bankReconciliationPayload();
    $payload['notes'] = 'Updated notes';

    putJson("/api/bank-reconciliations/{$reconciliation->id}", $payload)->assertOk();

    assertDatabaseHas('bank_reconciliations', ['id' => $reconciliation->id, 'notes' => 'Updated notes']);
});

test('it can delete a bank reconciliation', function () {
    $reconciliation = BankReconciliation::factory()->create();

    deleteJson("/api/bank-reconciliations/{$reconciliation->id}")->assertNoContent();

    assertDatabaseMissing('bank_reconciliations', ['id' => $reconciliation->id]);
});

test('it can export bank reconciliations', function () {
    Excel::fake();
    BankReconciliation::factory()->create();

    postJson('/api/bank-reconciliations/export', [])->assertOk()->assertJsonStructure(['url', 'filename']);
});

test('it can complete a bank reconciliation when difference is zero', function () {
    $reconciliation = BankReconciliation::factory()->create(['difference' => 0, 'status' => 'in_progress']);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.status', 'completed');
});

test('it cannot complete when difference is not zero', function () {
    $reconciliation = BankReconciliation::factory()->create(['difference' => 10, 'status' => 'in_progress']);

    postJson("/api/bank-reconciliations/{$reconciliation->id}/complete")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('difference');
});

test('it can add an item to a bank reconciliation', function () {
    $reconciliation = BankReconciliation::factory()->create();

    postJson("/api/bank-reconciliations/{$reconciliation->id}/items", [
        'transaction_date' => '2026-01-20',
        'description' => 'Adjustment',
        'debit' => 100,
        'credit' => 0,
        'type' => 'adjustment',
        'is_reconciled' => true,
    ])->assertCreated()->assertJsonStructure(['data' => ['id', 'description']]);

    assertDatabaseHas('bank_reconciliation_items', ['bank_reconciliation_id' => $reconciliation->id, 'description' => 'Adjustment']);
});

test('it can remove an item from a bank reconciliation', function () {
    $reconciliation = BankReconciliation::factory()->create();
    $item = BankReconciliationItem::factory()->create(['bank_reconciliation_id' => $reconciliation->id]);

    deleteJson("/api/bank-reconciliations/{$reconciliation->id}/items/{$item->id}")->assertNoContent();

    assertDatabaseMissing('bank_reconciliation_items', ['id' => $item->id]);
});
