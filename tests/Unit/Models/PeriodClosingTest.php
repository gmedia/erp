<?php

use App\Models\PeriodClosing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('period-closings');

test('it has correct fillable attributes', function () {
    expect((new PeriodClosing())->getFillable())->toBe(['fiscal_year_id', 'period_month', 'period_year', 'closing_type', 'status', 'closing_journal_entry_id', 'retained_earnings_account_id', 'net_income', 'notes', 'closed_by', 'closed_at', 'reopened_by', 'reopened_at', 'created_by']);
});

test('it belongs to fiscal year', function () {
    expect((new PeriodClosing())->fiscalYear())->toBeInstanceOf(BelongsTo::class);
});

test('isClosed returns true when status is closed', function () {
    expect(PeriodClosing::factory()->make(['status' => 'closed'])->isClosed())->toBeTrue();
});

test('isAnnual returns true when closing_type is annual', function () {
    expect(PeriodClosing::factory()->annual()->make()->isAnnual())->toBeTrue();
});
