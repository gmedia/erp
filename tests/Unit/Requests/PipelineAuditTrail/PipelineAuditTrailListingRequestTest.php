<?php

use App\Http\Requests\PipelineAuditTrail\ExportPipelineAuditTrailRequest;
use App\Http\Requests\PipelineAuditTrail\IndexPipelineAuditTrailRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('pipeline-audit-trail');

test('pipeline audit trail index request validates export flag', function () {
    $request = new IndexPipelineAuditTrailRequest;
    $validator = Validator::make([
        'export' => 'yes',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});

test('pipeline audit trail export request validates format enum', function () {
    $request = new ExportPipelineAuditTrailRequest;
    $validator = Validator::make([
        'format' => 'pdf',
    ], $request->rules());

    expect($validator->fails())->toBeTrue();
});