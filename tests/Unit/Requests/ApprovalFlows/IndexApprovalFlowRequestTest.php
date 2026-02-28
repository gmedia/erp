<?php

use App\Http\Requests\ApprovalFlows\IndexApprovalFlowRequest;

uses()->group('approval-flows');

test('authorize returns true', function () {
    $request = new IndexApprovalFlowRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexApprovalFlowRequest;

    expect($request->rules())->toBe([
        'search' => ['nullable', 'string'],
        'approvable_type' => ['nullable', 'string'],
        'is_active' => ['nullable', 'boolean'],
        'sort_by' => ['nullable', 'string', 'in:id,name,code,approvable_type,is_active,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});
