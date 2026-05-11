<?php

use App\Models\FiscalYear;
use App\Models\RecurringJournal;
use App\Models\RecurringJournalLine;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class)->group('recurring-journals');

test('it has correct fillable attributes', function () {
    expect((new RecurringJournal())->getFillable())->toBe(['name', 'description', 'fiscal_year_id', 'frequency', 'next_run_date', 'last_run_date', 'end_date', 'total_amount', 'auto_post', 'is_active', 'created_by']);
});

test('it belongs to fiscal year', function () {
    expect((new RecurringJournal())->fiscalYear())->toBeInstanceOf(BelongsTo::class);
});

test('it has many lines', function () {
    expect((new RecurringJournal())->lines())->toBeInstanceOf(HasMany::class);
});

test('isBalanced returns true when balanced', function () {
    $journal = RecurringJournal::factory()->create();
    RecurringJournalLine::factory()->create(['recurring_journal_id' => $journal->id, 'debit' => 100, 'credit' => 0]);
    RecurringJournalLine::factory()->create(['recurring_journal_id' => $journal->id, 'debit' => 0, 'credit' => 100]);

    expect($journal->refresh()->isBalanced())->toBeTrue();
});

test('isBalanced returns false when unbalanced', function () {
    $journal = RecurringJournal::factory()->create();
    RecurringJournalLine::factory()->create(['recurring_journal_id' => $journal->id, 'debit' => 100, 'credit' => 0]);

    expect($journal->refresh()->isBalanced())->toBeFalse();
});

test('scope active filters correctly', function () {
    RecurringJournal::factory()->create(['is_active' => true]);
    RecurringJournal::factory()->inactive()->create();

    expect(RecurringJournal::active()->count())->toBe(1);
});

test('scope due filters correctly', function () {
    Carbon::setTestNow('2026-01-15');
    $fiscalYear = FiscalYear::factory()->create();
    RecurringJournal::factory()->create(['fiscal_year_id' => $fiscalYear->id, 'is_active' => true, 'next_run_date' => '2026-01-14', 'end_date' => null]);
    RecurringJournal::factory()->create(['fiscal_year_id' => $fiscalYear->id, 'is_active' => true, 'next_run_date' => '2026-01-16', 'end_date' => null]);

    expect(RecurringJournal::due()->count())->toBe(1);
    Carbon::setTestNow();
});
