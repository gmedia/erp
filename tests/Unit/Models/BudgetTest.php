<?php

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\FiscalYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class)->group('budgets');

test('it belongs to a fiscal year', function () {
    $fiscalYear = FiscalYear::factory()->create();
    $budget = Budget::factory()->create(['fiscal_year_id' => $fiscalYear->id]);

    expect($budget->fiscalYear)->toBeInstanceOf(FiscalYear::class)
        ->and($budget->fiscalYear->id)->toBe($fiscalYear->id);
});

test('it belongs to a creator', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create(['created_by' => $user->id]);

    expect($budget->creator)->toBeInstanceOf(User::class)
        ->and($budget->creator->id)->toBe($user->id);
});

test('it has many lines', function () {
    $budget = Budget::factory()->create();
    BudgetLine::factory()->count(3)->create(['budget_id' => $budget->id]);

    expect($budget->lines)->toHaveCount(3)
        ->each->toBeInstanceOf(BudgetLine::class);
});

test('it casts total_amount to decimal', function () {
    $budget = Budget::factory()->create(['total_amount' => 12345.67]);

    $fresh = $budget->fresh();

    expect($fresh->total_amount)->toBe('12345.67');
});

test('it casts approved_at to datetime', function () {
    $budget = Budget::factory()->approved()->create();

    expect($budget->approved_at)->toBeInstanceOf(Carbon::class);
});
