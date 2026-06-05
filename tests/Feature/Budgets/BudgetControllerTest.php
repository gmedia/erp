<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('budgets');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['budget', 'budget.create', 'budget.edit', 'budget.delete']);
    Sanctum::actingAs($this->user, ['*']);
});

function budgetPayload(?FiscalYear $fiscalYear = null): array
{
    $fiscalYear ??= FiscalYear::factory()->create(['status' => 'open']);
    $account1 = Account::factory()->create();
    $account2 = Account::factory()->create();

    return [
        'fiscal_year_id' => $fiscalYear->id,
        'name' => 'Q1 2026 Operating Budget',
        'description' => 'Quarterly operating expenses',
        'budget_type' => 'operational',
        'lines' => [
            [
                'account_id' => $account1->id,
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
                'allocated_amount' => 50000,
                'notes' => 'January allocation',
            ],
            [
                'account_id' => $account2->id,
                'period_start' => '2026-01-01',
                'period_end' => '2026-01-31',
                'allocated_amount' => 30000,
            ],
        ],
    ];
}

test('it can list budgets', function () {
    Budget::factory()->count(2)->create();

    getJson('/api/budgets')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('it can create a budget with lines', function () {
    $payload = budgetPayload();

    $response = postJson('/api/budgets', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Q1 2026 Operating Budget');

    assertDatabaseHas('budgets', ['name' => 'Q1 2026 Operating Budget']);
    assertDatabaseHas('budget_lines', ['allocated_amount' => '50000.00']);
    assertDatabaseHas('budget_lines', ['allocated_amount' => '30000.00']);
});

test('it can show a budget with lines', function () {
    $budget = Budget::factory()->create();
    BudgetLine::factory()->count(2)->create(['budget_id' => $budget->id]);

    getJson("/api/budgets/{$budget->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $budget->id)
        ->assertJsonStructure(['data' => ['id', 'name', 'lines']]);
});

test('it can update a draft budget', function () {
    $budget = Budget::factory()->create(['status' => 'draft']);
    $payload = budgetPayload($budget->fiscalYear);
    $payload['name'] = 'Updated Budget Name';

    putJson("/api/budgets/{$budget->id}", $payload)->assertOk();

    assertDatabaseHas('budgets', ['id' => $budget->id, 'name' => 'Updated Budget Name']);
});

test('it cannot update a locked budget', function () {
    $budget = Budget::factory()->locked()->create();
    $payload = budgetPayload($budget->fiscalYear);
    $payload['name'] = 'Should Not Update';

    putJson("/api/budgets/{$budget->id}", $payload)->assertUnprocessable();
});

test('it can delete a draft budget', function () {
    $budget = Budget::factory()->create(['status' => 'draft']);

    deleteJson("/api/budgets/{$budget->id}")->assertNoContent();

    assertDatabaseMissing('budgets', ['id' => $budget->id]);
});

test('it cannot delete an approved budget', function () {
    $budget = Budget::factory()->approved()->create();

    deleteJson("/api/budgets/{$budget->id}")->assertUnprocessable();
});

test('it can approve a draft budget', function () {
    $budget = Budget::factory()->create(['status' => 'draft']);

    postJson("/api/budgets/{$budget->id}/approve")->assertOk();

    $budget->refresh();
    expect($budget->status)->toBe('approved')
        ->and($budget->approved_by)->toBe($this->user->id)
        ->and($budget->approved_at)->not->toBeNull();
});

test('it cannot approve a non-draft budget', function () {
    $budget = Budget::factory()->approved()->create();

    postJson("/api/budgets/{$budget->id}/approve")->assertUnprocessable();
});

test('it can lock an approved budget', function () {
    $budget = Budget::factory()->approved()->create();

    postJson("/api/budgets/{$budget->id}/lock")->assertOk();

    expect($budget->refresh()->status)->toBe('locked');
});

test('it cannot lock a draft budget', function () {
    $budget = Budget::factory()->create(['status' => 'draft']);

    postJson("/api/budgets/{$budget->id}/lock")->assertUnprocessable();
});

test('it filters budgets by status', function () {
    Budget::factory()->create(['status' => 'draft']);
    Budget::factory()->approved()->create();

    getJson('/api/budgets?status=draft')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', 'draft');
});

test('it filters budgets by fiscal_year_id', function () {
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
    Budget::factory()->create(['fiscal_year_id' => $fiscalYear->id]);
    Budget::factory()->create();

    getJson("/api/budgets?fiscal_year_id={$fiscalYear->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('it searches budgets by name', function () {
    Budget::factory()->create(['name' => 'Marketing Budget']);
    Budget::factory()->create(['name' => 'Operations Budget']);

    getJson('/api/budgets?search=Marketing')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Marketing Budget');
});

test('it returns 403 without budget permission', function () {
    $user = createTestUserWithPermissions([]);
    Sanctum::actingAs($user, ['*']);

    getJson('/api/budgets')->assertForbidden();
});

test('it validates store request', function () {
    postJson('/api/budgets', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'fiscal_year_id', 'budget_type', 'lines']);
});
