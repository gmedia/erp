<?php

use App\Http\Requests\ApprovalFlows\StoreApprovalFlowRequest;

uses()->group('approval-flows');

test('authorize returns true', function () {
    $request = new StoreApprovalFlowRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new StoreApprovalFlowRequest;

    expect($request->rules())->toBe([
        'name' => 'required|string|max:255',
        'code' => 'required|string|max:255|unique:approval_flows,code',
        'approvable_type' => 'required|string|max:255',
        'description' => 'nullable|string',
        'is_active' => 'boolean',
        'conditions' => 'nullable|array',

        'steps' => 'required|array|min:1',
        'steps.*.name' => 'required|string|max:255',
        'steps.*.approver_type' => 'required|in:user',
        'steps.*.approver_user_id' => 'required|exists:users,id',
        'steps.*.approver_role_id' => 'prohibited',
        'steps.*.approver_department_id' => 'prohibited',
        'steps.*.required_action' => 'required|in:approve,review,acknowledge',
        'steps.*.auto_approve_after_hours' => 'nullable|integer|min:0',
        'steps.*.escalate_after_hours' => 'nullable|integer|min:0',
        'steps.*.escalation_user_id' => 'nullable|exists:users,id',
        'steps.*.can_reject' => 'boolean',
    ]);
});
