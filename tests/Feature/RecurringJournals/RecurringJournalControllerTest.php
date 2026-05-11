<?php

use App\Models\Account;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\RecurringJournal;
use App\Models\RecurringJournalLine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('recurring-journals');

beforeEach(function () {
    $this->user = createTestUserWithPermissions(['recurring_journal', 'recurring_journal.create', 'recurring_journal.edit', 'recurring_journal.delete']);
    Sanctum::actingAs($this->user, ['*']);
});

function recurringJournalPayload(?FiscalYear $fiscalYear = null): array
{
    $fiscalYear ??= FiscalYear::factory()->create(['status' => 'open']);
    $debitAccount = Account::factory()->create();
    $creditAccount = Account::factory()->create();

    return [
        'name' => 'Monthly Accrual',
        'description' => 'Monthly accrual template',
        'fiscal_year_id' => $fiscalYear->id,
        'frequency' => 'monthly',
        'next_run_date' => '2026-01-15',
        'auto_post' => true,
        'is_active' => true,
        'lines' => [
            ['account_id' => $debitAccount->id, 'debit' => 1000, 'credit' => 0, 'memo' => 'Debit'],
            ['account_id' => $creditAccount->id, 'debit' => 0, 'credit' => 1000, 'memo' => 'Credit'],
        ],
    ];
}

test('it can list recurring journals', function () {
    RecurringJournal::factory()->count(3)->create();

    getJson('/api/recurring-journals')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'frequency', 'next_run_date', 'total_amount']], 'meta']);
});

test('it can create a recurring journal with lines', function () {
    $response = postJson('/api/recurring-journals', recurringJournalPayload());

    $response->assertCreated()->assertJsonStructure(['data' => ['id', 'name', 'lines']]);
    assertDatabaseHas('recurring_journals', ['name' => 'Monthly Accrual', 'total_amount' => 1000]);
    expect(RecurringJournal::first()->lines)->toHaveCount(2);
});

test('it can show a recurring journal', function () {
    $journal = RecurringJournal::factory()->create();
    RecurringJournalLine::factory()->count(2)->create(['recurring_journal_id' => $journal->id]);

    getJson("/api/recurring-journals/{$journal->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $journal->id)
        ->assertJsonStructure(['data' => ['lines']]);
});

test('it can update a recurring journal', function () {
    $journal = RecurringJournal::factory()->create();
    $payload = recurringJournalPayload($journal->fiscalYear);
    $payload['name'] = 'Updated Recurring Journal';

    putJson("/api/recurring-journals/{$journal->id}", $payload)->assertOk();

    assertDatabaseHas('recurring_journals', ['id' => $journal->id, 'name' => 'Updated Recurring Journal']);
    expect($journal->refresh()->lines)->toHaveCount(2);
});

test('it can delete a recurring journal', function () {
    $journal = RecurringJournal::factory()->create();

    deleteJson("/api/recurring-journals/{$journal->id}")->assertNoContent();

    assertDatabaseMissing('recurring_journals', ['id' => $journal->id]);
});

test('it can export recurring journals', function () {
    Excel::fake();
    RecurringJournal::factory()->create();

    postJson('/api/recurring-journals/export', [])->assertOk()->assertJsonStructure(['url', 'filename']);
});

test('it can execute a recurring journal', function () {
    $journal = RecurringJournal::factory()->create(['auto_post' => true, 'is_active' => true, 'next_run_date' => '2026-01-15']);
    $accounts = Account::factory()->count(2)->create();
    RecurringJournalLine::factory()->create(['recurring_journal_id' => $journal->id, 'account_id' => $accounts[0]->id, 'debit' => 1000, 'credit' => 0]);
    RecurringJournalLine::factory()->create(['recurring_journal_id' => $journal->id, 'account_id' => $accounts[1]->id, 'debit' => 0, 'credit' => 1000]);

    postJson("/api/recurring-journals/{$journal->id}/execute")
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'entry_number', 'lines']]);

    expect(JournalEntry::where('source_id', $journal->id)->where('journal_type', 'recurring')->exists())->toBeTrue();
});

test('it validates lines must be balanced', function () {
    $payload = recurringJournalPayload();
    $payload['lines'][1]['credit'] = 900;

    postJson('/api/recurring-journals', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lines');
});
