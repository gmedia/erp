<?php

use App\Http\Requests\ApprovalFlows\UpdateApprovalFlowRequest;
use App\Models\ApprovalFlow;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;

uses()->group('approval-flows');

test('authorize returns true', function () {
    $request = new UpdateApprovalFlowRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $flow = new ApprovalFlow(['id' => 1]);
    
    $request = new UpdateApprovalFlowRequest;
    
    // Mock the route parameter
    $route = Mockery::mock(Route::class);
    $route->shouldReceive('parameter')->with('approval_flow', null)->andReturn($flow);
    $request->setRouteResolver(fn() => $route);
    
    expect($request->rules())->toEqual([
        'name' => 'sometimes|required|string|max:255',
        'code' => [
            'sometimes',
            'required',
            'string',
            'max:255',
            Rule::unique('approval_flows')->ignore($flow->id),
        ],
        'approvable_type' => 'sometimes|required|string|max:255',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
        'conditions' => 'nullable|array',
        
        'steps' => 'sometimes|required|array|min:1',
        'steps.*.id' => 'nullable|exists:approval_flow_steps,id',
        'steps.*.name' => 'required|string|max:255',
        'steps.*.approver_type' => 'required|in:user,role,department_head',
        'steps.*.approver_user_id' => 'required_if:steps.*.approver_type,user|nullable|exists:users,id',
        'steps.*.approver_role_id' => 'required_if:steps.*.approver_type,role|nullable|integer',
        'steps.*.approver_department_id' => 'required_if:steps.*.approver_type,department_head|nullable|exists:departments,id',
        'steps.*.required_action' => 'required|in:approve,review,acknowledge',
        'steps.*.auto_approve_after_hours' => 'nullable|integer|min:0',
        'steps.*.escalate_after_hours' => 'nullable|integer|min:0',
        'steps.*.escalation_user_id' => 'nullable|exists:users,id',
        'steps.*.can_reject' => 'boolean',
    ]);
});
