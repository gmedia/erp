<?php

namespace Tests\Unit\Requests\ApprovalDelegations;

use App\Http\Requests\ApprovalDelegations\IndexApprovalDelegationRequest;
use Illuminate\Support\Facades\Validator;
uses()->group('approval-delegations');

test('authorize returns true', function () {
    $request = new IndexApprovalDelegationRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexApprovalDelegationRequest();
    
    expect($request->rules())->toBe([
        'search' => ['nullable', 'string'],
        'delegator_user_id' => ['nullable', 'exists:users,id'],
        'delegate_user_id' => ['nullable', 'exists:users,id'],
        'is_active' => ['nullable', 'string', 'in:true,false,1,0'],
        'start_date_from' => ['nullable', 'date'],
        'start_date_to' => ['nullable', 'date'],
        'sort_by' => ['nullable', 'string', 'in:id,delegator_user_id,delegate_user_id,approvable_type,start_date,end_date,is_active,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});
