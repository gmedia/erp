<?php

use App\Models\AccountBalance;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('general-ledger');

test('it has correct fillable attributes', function () {
    expect((new AccountBalance())->getFillable())->toBe(['account_id', 'fiscal_year_id', 'period_month', 'period_year', 'opening_balance', 'debit_total', 'credit_total', 'closing_balance', 'movement', 'last_recalculated_at']);
});

test('it belongs to account', function () {
    expect((new AccountBalance())->account())->toBeInstanceOf(BelongsTo::class);
});

test('it belongs to fiscal year', function () {
    expect((new AccountBalance())->fiscalYear())->toBeInstanceOf(BelongsTo::class);
});
