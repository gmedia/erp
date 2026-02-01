<?php

use App\Http\Requests\CoaVersions\IndexCoaVersionRequest;

uses()->group('coa-versions');

test('index rules are correct', function () {
    $rules = (new IndexCoaVersionRequest())->rules();

    expect($rules)->toHaveKeys(['search', 'status', 'fiscal_year_id', 'sort_direction', 'sort_by', 'per_page']);
});
