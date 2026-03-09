<?php

use App\Actions\ApprovalFlows\ExportApprovalFlowsAction;
use App\Http\Requests\ApprovalFlows\ExportApprovalFlowRequest;
use App\Models\ApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('approval-flows');

test('execute exports approval flows and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportApprovalFlowsAction;

    ApprovalFlow::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportApprovalFlowRequest::class);
    $request->shouldReceive('validated')->andReturn([]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('approval_flows_export_')
        ->and($data['filename'])->toContain('.xlsx');
});

test('execute exports with search filter', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportApprovalFlowsAction;

    ApprovalFlow::factory()->create(['name' => 'Target Flow']);
    ApprovalFlow::factory()->create(['name' => 'Other Flow']);

    // Mock request with search
    $request = Mockery::mock(ExportApprovalFlowRequest::class);
    $request->shouldReceive('validated')->andReturn(['search' => 'target']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(JsonResponse::class);
});
