<?php

use App\Http\Requests\ApprovalAuditTrail\ExportApprovalAuditTrailRequest;
use App\Http\Requests\ApprovalAuditTrail\IndexApprovalAuditTrailRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('approval-audit-trail');

test('approval audit trail index request validates pagination rule', function () {
    $request = new IndexApprovalAuditTrailRequest;
    $validator = Validator::make([
        'per_page' => 101,
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});

test('approval audit trail export request reuses shared sort validation', function () {
    $request = new ExportApprovalAuditTrailRequest;
    $validator = Validator::make([
        'sort_by' => 'invalid_field',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});
