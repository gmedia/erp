<?php

use App\Domain\Accounts\AccountFilterService;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounts');

beforeEach(function () {
    $this->filterService = new AccountFilterService();
});

test('it can filter by type', function () {
    Account::factory()->create(['type' => 'asset']);
    Account::factory()->create(['type' => 'liability']);

    $query = Account::query();
    $this->filterService->applyAdvancedFilters($query, ['type' => 'asset']);
    
    expect($query->count())->toBe(1)
        ->and($query->first()->type)->toBe('asset');
});

test('it can filter by active status', function () {
    Account::factory()->create(['is_active' => true]);
    Account::factory()->create(['is_active' => false]);

    $query = Account::query();
    $this->filterService->applyAdvancedFilters($query, ['is_active' => true]);
    
    expect($query->count())->toBe(1)
        ->and((bool)$query->first()->is_active)->toBeTrue();
});

test('it can search by code or name', function () {
    Account::factory()->create(['code' => '111', 'name' => 'Cash']);
    Account::factory()->create(['code' => '222', 'name' => 'Supplies']);

    $query = Account::query();
    $this->filterService->applySearch($query, 'Cash', ['code', 'name']);
    expect($query->count())->toBe(1);

    $query = Account::query();
    $this->filterService->applySearch($query, '222', ['code', 'name']);
    expect($query->count())->toBe(1);
});
