<?php

use App\Http\Resources\ApprovalFlows\ApprovalFlowResource;
use App\Models\ApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('approval-flows');

test('resource transforms approval flow correctly', function () {
    $flow = ApprovalFlow::factory()->create([
        'name' => 'Test Flow',
        'code' => 'TEST_FLOW',
        'approvable_type' => 'App\\Models\\AssetMovement',
        'description' => 'Test Description',
        'is_active' => true,
    ]);

    // Add a step
    $flow->steps()->create([
        'step_order' => 1,
        'name' => 'Test Step',
        'approver_type' => 'user',
        'required_action' => 'approve',
    ]);

    $flow->load('steps');

    $request = request();
    $resource = (new ApprovalFlowResource($flow))->toArray($request);

    expect($resource)
        ->toBeArray()
        ->toHaveKeys([
            'id',
            'name',
            'code',
            'approvable_type',
            'description',
            'is_active',
            'conditions',
            'steps',
            'created_at',
            'updated_at',
        ])
        ->and($resource['id'])->toBe($flow->id)
        ->and($resource['name'])->toBe('Test Flow')
        ->and($resource['code'])->toBe('TEST_FLOW')
        ->and($resource['steps'])->toBeIterable()
        ->and($resource['steps'])->toHaveCount(1);
});
