<?php

use App\Actions\ApprovalDelegations\ExportApprovalDelegationsAction;
use App\Http\Requests\ApprovalDelegations\ExportApprovalDelegationRequest;
use App\Models\ApprovalDelegation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('approval-delegations');

test('executes export and returns file info', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));
    Storage::fake('public');
    Excel::fake();

    ApprovalDelegation::factory()->count(5)->create();

    $request = Mockery::mock(ExportApprovalDelegationRequest::class);
    $request->shouldReceive('validated')->once()->andReturn([]);

    $action = app(ExportApprovalDelegationsAction::class);
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toBe('approval_delegations_export_2026-01-01_10-00-00.xlsx');

    Excel::assertStored('exports/' . $data['filename'], 'public');

    Carbon::setTestNow();
});

test('executes export with filters', function () {
    Storage::fake('public');
    Excel::fake();

    ApprovalDelegation::factory()->create(['is_active' => true]);
    ApprovalDelegation::factory()->create(['is_active' => false]);

    $request = Mockery::mock(ExportApprovalDelegationRequest::class);
    $request->shouldReceive('validated')->once()->andReturn(['is_active' => true]);

    $action = app(ExportApprovalDelegationsAction::class);
    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(JsonResponse::class);
});
