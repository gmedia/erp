<?php

use App\Http\Requests\FiscalYears\ExportFiscalYearRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('fiscal-years');

test('export rules are correct', function () {
    $rules = (new ExportFiscalYearRequest())->rules();

    expect($rules)->toHaveKeys(['search', 'status', 'sort_by', 'sort_direction']);
});
