<?php

use App\Http\Requests\CoaVersions\UpdateCoaVersionRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('coa-versions');

test('update rules have required fields', function () {
    $rules = (new UpdateCoaVersionRequest())->rules();

    expect($rules['name'])->toContain('required')
        ->and($rules['fiscal_year_id'])->toContain('required')
        ->and($rules['status'])->toContain('required');
});
