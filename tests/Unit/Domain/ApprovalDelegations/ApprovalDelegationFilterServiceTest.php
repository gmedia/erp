<?php

use App\Domain\ApprovalDelegations\ApprovalDelegationFilterService;
use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class)->group('approval-delegations');

test('applyAdvancedFilters filters by delegator_user_id', function () {
    $delegator = User::factory()->create();
    ApprovalDelegation::factory()->create(['delegator_user_id' => $delegator->id]);
    ApprovalDelegation::factory()->create();

    $filters = ['delegator_user_id' => $delegator->id];
    $query = ApprovalDelegation::query();
    
    $service = new ApprovalDelegationFilterService();
    $service->applyAdvancedFilters($query, $filters);

    expect($query->count())->toBe(1)
        ->and($query->first()->delegator_user_id)->toBe($delegator->id);
});

test('applyAdvancedFilters filters by delegate_user_id', function () {
    $delegate = User::factory()->create();
    ApprovalDelegation::factory()->create(['delegate_user_id' => $delegate->id]);
    ApprovalDelegation::factory()->create();

    $filters = ['delegate_user_id' => $delegate->id];
    $query = ApprovalDelegation::query();
    
    $service = new ApprovalDelegationFilterService();
    $service->applyAdvancedFilters($query, $filters);

    expect($query->count())->toBe(1)
        ->and($query->first()->delegate_user_id)->toBe($delegate->id);
});

test('applyAdvancedFilters filters by is_active', function () {
    ApprovalDelegation::factory()->create(['is_active' => true]);
    $inactive = ApprovalDelegation::factory()->create(['is_active' => false]);

    $filters = ['is_active' => 'false'];
    $query = ApprovalDelegation::query();
    
    $service = new ApprovalDelegationFilterService();
    $service->applyAdvancedFilters($query, $filters);

    expect($query->count())->toBe(1)
        ->and($query->first()->id)->toBe($inactive->id);
});

test('applyAdvancedFilters filters by start_date_from and start_date_to', function () {
    ApprovalDelegation::factory()->create(['start_date' => '2026-01-15']);
    ApprovalDelegation::factory()->create(['start_date' => '2026-02-15']);

    $filters = [
        'start_date_from' => '2026-01-01',
        'start_date_to' => '2026-01-31'
    ];
    
    $query = ApprovalDelegation::query();
    
    $service = new ApprovalDelegationFilterService();
    $service->applyAdvancedFilters($query, $filters);

    expect($query->count())->toBe(1)
        ->and($query->first()->start_date->format('Y-m-d'))->toBe('2026-01-15');
});
