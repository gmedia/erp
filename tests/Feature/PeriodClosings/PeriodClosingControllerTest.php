<?php

use App\Models\Account;
use App\Models\AccountBalance;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('period-closings');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['period_closing', 'period_closing.create', 'period_closing.edit']);
    Sanctum::actingAs($this->user, ['*']);
});

test('it can list period closings', function () {
    PeriodClosing::factory()->count(3)->create();

    getJson('/api/period-closings')->assertOk()->assertJsonStructure(['data' => [['id', 'period_year', 'closing_type', 'status']], 'meta']);
});

test('it can create a period closing', function () {
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);

    postJson('/api/period-closings', [
        'fiscal_year_id' => $fiscalYear->id,
        'period_month' => 1,
        'period_year' => 2026,
        'closing_type' => 'monthly',
        'notes' => 'January closing',
    ])->assertCreated()->assertJsonPath('data.status', 'draft');

    assertDatabaseHas('period_closings', ['period_month' => 1, 'period_year' => 2026, 'status' => 'draft']);
});

test('it can show a period closing', function () {
    $periodClosing = PeriodClosing::factory()->create();

    getJson("/api/period-closings/{$periodClosing->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $periodClosing->id);
});

test('it can close a period monthly', function () {
    $periodClosing = PeriodClosing::factory()->create(['closing_type' => 'monthly', 'period_month' => 1, 'period_year' => 2026, 'status' => 'draft']);

    postJson("/api/period-closings/{$periodClosing->id}/close")
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');
});

test('it can close a period annual and generates closing journal entry', function () {
    $fiscalYear = FiscalYear::factory()->create(['status' => 'open']);
    $revenue = Account::factory()->create(['type' => 'revenue', 'normal_balance' => 'credit']);
    $expense = Account::factory()->create(['type' => 'expense', 'normal_balance' => 'debit']);
    $retained = Account::factory()->create(['type' => 'equity', 'normal_balance' => 'credit']);
    AccountBalance::factory()->create(['account_id' => $revenue->id, 'fiscal_year_id' => $fiscalYear->id, 'period_month' => 12, 'period_year' => 2026, 'closing_balance' => 5000]);
    AccountBalance::factory()->create(['account_id' => $expense->id, 'fiscal_year_id' => $fiscalYear->id, 'period_month' => 12, 'period_year' => 2026, 'closing_balance' => 2000]);
    $periodClosing = PeriodClosing::factory()->annual()->create(['fiscal_year_id' => $fiscalYear->id, 'period_year' => 2026, 'status' => 'draft', 'retained_earnings_account_id' => $retained->id]);

    postJson("/api/period-closings/{$periodClosing->id}/close")
        ->assertOk()
        ->assertJsonPath('data.status', 'closed');

    expect(JournalEntry::where('source_id', $periodClosing->id)->where('journal_type', 'closing')->exists())->toBeTrue();
});

test('it can reopen a closed period', function () {
    $periodClosing = PeriodClosing::factory()->closed()->create();

    postJson("/api/period-closings/{$periodClosing->id}/reopen")
        ->assertOk()
        ->assertJsonPath('data.status', 'reopened');
});

test('it cannot close an already closed period', function () {
    $periodClosing = PeriodClosing::factory()->closed()->create();

    postJson("/api/period-closings/{$periodClosing->id}/close")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

test('it can export period closings', function () {
    Excel::fake();
    PeriodClosing::factory()->create();

    postJson('/api/period-closings/export', [])->assertOk()->assertJsonStructure(['url', 'filename']);
});
