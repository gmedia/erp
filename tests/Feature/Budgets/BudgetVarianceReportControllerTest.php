<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('budget-variance-report');

function seedBudgetWithActuals(): Budget
{
    $fiscalYear = FiscalYear::factory()->create();
    $budget = Budget::factory()->create(['fiscal_year_id' => $fiscalYear->id]);

    $overspentAccount = Account::factory()->create(['type' => 'expense']);
    BudgetLine::factory()->create([
        'budget_id' => $budget->id,
        'account_id' => $overspentAccount->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'allocated_amount' => 1000,
    ]);
    $overspentEntry = JournalEntry::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'posted',
        'entry_date' => '2026-01-15',
    ]);
    JournalEntryLine::factory()->create([
        'journal_entry_id' => $overspentEntry->id,
        'account_id' => $overspentAccount->id,
        'debit' => 1500,
        'credit' => 0,
    ]);

    $healthyAccount = Account::factory()->create(['type' => 'revenue']);
    BudgetLine::factory()->create([
        'budget_id' => $budget->id,
        'account_id' => $healthyAccount->id,
        'period_start' => '2026-01-01',
        'period_end' => '2026-01-31',
        'allocated_amount' => 5000,
    ]);

    return $budget;
}

test('it requires budget_variance_report permission', function () {
    Sanctum::actingAs(createTestUserWithPermissions([]), ['*']);
    $budget = Budget::factory()->create();

    getJson('/api/reports/budget-variance?budget_id=' . $budget->id)
        ->assertForbidden();
});

test('it returns variance data with summary and meta', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['budget_variance_report']), ['*']);
    $budget = seedBudgetWithActuals();

    getJson('/api/reports/budget-variance?budget_id=' . $budget->id)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['account_id', 'account_code', 'account_name', 'account_type', 'allocated', 'actual', 'available', 'variance_percent', 'status']],
            'summary' => ['total_allocated', 'total_actual', 'total_available', 'overall_variance_percent'],
            'meta' => ['budget_id', 'budget_name', 'fiscal_year_id'],
        ])
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.budget_id', $budget->id);
});

test('it validates that budget_id is required', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['budget_variance_report']), ['*']);

    getJson('/api/reports/budget-variance')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['budget_id']);
});

test('it validates that budget_id must exist', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['budget_variance_report']), ['*']);

    getJson('/api/reports/budget-variance?budget_id=999999')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['budget_id']);
});

test('it filters variance rows by status', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['budget_variance_report']), ['*']);
    $budget = seedBudgetWithActuals();

    $response = getJson('/api/reports/budget-variance?budget_id=' . $budget->id . '&status=over_budget')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    expect($response->json('data.0.status'))->toBe('over_budget');
});

test('it filters variance rows by account type', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['budget_variance_report']), ['*']);
    $budget = seedBudgetWithActuals();

    $response = getJson('/api/reports/budget-variance?budget_id=' . $budget->id . '&account_type=revenue')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    expect($response->json('data.0.account_type'))->toBe('revenue');
});

test('it rejects an invalid status filter', function () {
    Sanctum::actingAs(createTestUserWithPermissions(['budget_variance_report']), ['*']);
    $budget = Budget::factory()->create();

    getJson('/api/reports/budget-variance?budget_id=' . $budget->id . '&status=bogus')
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('it exports budget variance to xlsx', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-11 10:00:00'));
    Excel::fake();
    Storage::fake('public');
    Sanctum::actingAs(createTestUserWithPermissions(['budget_variance_report']), ['*']);
    $budget = seedBudgetWithActuals();

    $response = postJson('/api/reports/budget-variance/export', ['budget_id' => $budget->id])
        ->assertOk()
        ->assertJsonStructure(['url', 'filename']);

    expect($response->json('filename'))->toEndWith('.xlsx');
    Carbon::setTestNow();
});

test('it blocks export without permission', function () {
    Sanctum::actingAs(createTestUserWithPermissions([]), ['*']);
    $budget = Budget::factory()->create();

    postJson('/api/reports/budget-variance/export', ['budget_id' => $budget->id])
        ->assertForbidden();
});
