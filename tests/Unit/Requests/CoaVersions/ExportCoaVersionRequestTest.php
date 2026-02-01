<?php

use App\Http\Requests\CoaVersions\ExportCoaVersionRequest;

uses()->group('coa-versions');

test('export rules are correct', function () {
    $rules = (new ExportCoaVersionRequest())->rules();

    expect($rules)->toHaveKeys(['search', 'status', 'fiscal_year_id', 'sort_direction', 'sort_by']);
});
