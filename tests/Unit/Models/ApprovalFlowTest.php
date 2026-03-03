<?php

use App\Models\ApprovalFlow;
use App\Models\ApprovalFlowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class)->group('approval-flows');

test('factory creates a valid approval flow', function () {
    $flow = ApprovalFlow::factory()->create();

    assertDatabaseHas('approval_flows', ['id' => $flow->id]);

    expect($flow->getAttributes())->toMatchArray([
        'name' => $flow->name,
        'code' => $flow->code,
        'approvable_type' => $flow->approvable_type,
        'description' => $flow->description,
        'is_active' => $flow->is_active,
    ]);
});

test('approval flow has many steps', function () {
    $flow = ApprovalFlow::factory()->create();
    $step = ApprovalFlowStep::factory()->create(['approval_flow_id' => $flow->id]);

    expect($flow->steps)->toHaveCount(1)
        ->and($flow->steps->first())->toBeInstanceOf(ApprovalFlowStep::class)
        ->and($flow->steps->first()->id)->toBe($step->id);
});

test('fillable attributes are defined correctly', function () {
    $fillable = (new ApprovalFlow)->getFillable();

    expect($fillable)->toBe([
        'name',
        'code',
        'approvable_type',
        'description',
        'is_active',
        'conditions',
        'created_by',
    ]);
});
