<?php

use App\Http\Requests\ApprovalFlows\ExportApprovalFlowRequest;

uses()->group('approval-flows');

test('authorize returns true', function () {
    $request = new ExportApprovalFlowRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new ExportApprovalFlowRequest;

    expect($request->rules())->toBe([
        'search' => ['nullable', 'string'],
        'approvable_type' => ['nullable', 'string'],
        'is_active' => ['nullable', 'boolean'],
        'sort_by' => ['nullable', 'string', 'in:name,code,approvable_type,is_active,created_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
    ]);
});
