<?php

use App\Actions\ApprovalDelegations\ExportApprovalDelegationsAction;
use App\Models\ApprovalDelegation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('approval-delegations');

test('executes export and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    ApprovalDelegation::factory()->count(5)->create();

    $action = app(ExportApprovalDelegationsAction::class);
    $result = $action->execute([]);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('approval_delegations_export_')
        ->and($data['filename'])->toContain('.xlsx');
});

test('executes export with filters', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    ApprovalDelegation::factory()->create(['is_active' => true]);
    ApprovalDelegation::factory()->create(['is_active' => false]);

    $action = app(ExportApprovalDelegationsAction::class);
    $result = $action->execute(['is_active' => true]);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
