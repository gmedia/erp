<?php

use App\Http\Requests\JournalEntries\IndexJournalEntryRequest;
use Illuminate\Support\Facades\Validator;

uses()->group('journal-entries');

test('it authorizes any user', function () {
    $request = new IndexJournalEntryRequest();
    expect($request->authorize())->toBeTrue();
});

test('it validates optional filter fields', function () {
    $data = [
        'search' => 'JV-001',
        'status' => 'draft',
        'start_date' => '2023-01-01',
        'end_date' => '2023-01-31',
        'per_page' => 20,
        'page' => 2,
    ];

    $request = new IndexJournalEntryRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->passes())->toBeTrue();
});

test('it validates invalid status', function () {
    $data = ['status' => 'invalid_status'];
    
    $request = new IndexJournalEntryRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('status'))->toBeTrue();
});

test('it validates date formats', function () {
    $data = ['start_date' => 'invalid-date'];
    
    $request = new IndexJournalEntryRequest();
    $validator = Validator::make($data, $request->rules());

    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('start_date'))->toBeTrue();
});
