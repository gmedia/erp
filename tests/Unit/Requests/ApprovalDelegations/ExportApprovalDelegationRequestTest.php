<?php

namespace Tests\Unit\Requests\ApprovalDelegations;

use App\Http\Requests\ApprovalDelegations\ExportApprovalDelegationRequest;

uses()->group('approval-delegations');

test('authorize returns true', function () {
    $request = new ExportApprovalDelegationRequest;
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new ExportApprovalDelegationRequest;

    expect($request->rules())->toBe([
        'search' => ['nullable', 'string'],
        'delegator' => ['nullable', 'exists:users,id'],
        'delegate' => ['nullable', 'exists:users,id'],
        'is_active' => ['nullable', 'string', 'in:true,false,1,0'],
        'sort_by' => [
            'nullable',
            'string',
            'in:id,delegator_user_id,delegate_user_id,approvable_type,start_date,end_date,is_active,created_at',
        ],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
    ]);
});
