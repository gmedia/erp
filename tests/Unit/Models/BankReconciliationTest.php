<?php

use App\Models\BankReconciliation;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('bank-reconciliations');

test('it has correct fillable attributes', function () {
    expect((new BankReconciliation)->getFillable())->toBe(['account_id', 'fiscal_year_id', 'reconciliation_date', 'period_start', 'period_end', 'statement_balance', 'book_balance', 'reconciled_balance', 'difference', 'status', 'notes', 'completed_by', 'completed_at', 'created_by', 'journal_entry_id']);
});

test('it belongs to account', function () {
    expect((new BankReconciliation)->account())->toBeInstanceOf(BelongsTo::class);
});

test('it has many items', function () {
    expect((new BankReconciliation)->items())->toBeInstanceOf(HasMany::class);
});

test('isReconciled returns true when difference is zero', function () {
    $reconciliation = BankReconciliation::factory()->make(['difference' => 0]);

    expect($reconciliation->isReconciled())->toBeTrue();
});
