<?php

use App\Domain\Accounts\AccountFilterService;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounts');

test('it can filter by type', function () {
    $filterService = new AccountFilterService;

    Account::factory()->create(['type' => 'asset']);
    Account::factory()->create(['type' => 'liability']);

    $query = Account::query();
    $filterService->applyAdvancedFilters($query, ['type' => 'asset']);

    expect($query->count())->toBe(1)
        ->and($query->first()->type)->toBe('asset');
});

test('it can filter by active status', function () {
    $filterService = new AccountFilterService;

    Account::factory()->create(['is_active' => true]);
    Account::factory()->create(['is_active' => false]);

    $query = Account::query();
    $filterService->applyAdvancedFilters($query, ['is_active' => true]);

    expect($query->count())->toBe(1)
        ->and((bool) $query->first()->is_active)->toBeTrue();
});

test('it can filter inactive status using zero', function () {
    $filterService = new AccountFilterService;

    Account::factory()->create(['is_active' => true]);
    Account::factory()->create(['is_active' => false]);

    $query = Account::query();
    $filterService->applyAdvancedFilters($query, ['is_active' => 0]);

    expect($query->count())->toBe(1)
        ->and((bool) $query->first()->is_active)->toBeFalse();
});

test('it can search by code or name', function () {
    $filterService = new AccountFilterService;

    Account::factory()->create(['code' => '111', 'name' => 'Cash']);
    Account::factory()->create(['code' => '222', 'name' => 'Supplies']);

    $query = Account::query();
    $filterService->applySearch($query, 'Cash', ['code', 'name']);
    expect($query->count())->toBe(1);

    $query = Account::query();
    $filterService->applySearch($query, '222', ['code', 'name']);
    expect($query->count())->toBe(1);
});
