<?php

use App\Actions\ApprovalDelegations\ExportApprovalDelegationsAction;
use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-delegations');

test('executes query for export', function () {
    ApprovalDelegation::factory()->count(5)->create();

    $action = app(ExportApprovalDelegationsAction::class);
    $query = $action->execute([]);

    expect($query->count())->toBe(5);
});

test('executes query for export with filters', function () {
    ApprovalDelegation::factory()->create(['is_active' => true]);
    ApprovalDelegation::factory()->create(['is_active' => false]);

    $action = app(ExportApprovalDelegationsAction::class);
    $query = $action->execute(['is_active' => true]);

    expect($query->count())->toBe(1);
});
