<?php

use App\Http\Requests\StockTransfers\IndexStockTransferRequest;

uses()->group('stock-transfers');

test('authorize returns true', function () {
    $request = new IndexStockTransferRequest();
    expect($request->authorize())->toBeTrue();
});

test('rules returns correct validation rules', function () {
    $request = new IndexStockTransferRequest();

    expect($request->rules())->toEqual([
        'search' => ['nullable', 'string'],
        'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],
        'to_warehouse_id' => ['nullable', 'exists:warehouses,id'],
        'status' => ['nullable', 'string', 'in:draft,pending_approval,approved,in_transit,received,cancelled'],
        'transfer_date_from' => ['nullable', 'date'],
        'transfer_date_to' => ['nullable', 'date'],
        'expected_arrival_date_from' => ['nullable', 'date'],
        'expected_arrival_date_to' => ['nullable', 'date'],
        'sort_by' => ['nullable', 'string', 'in:id,transfer_number,from_warehouse_id,to_warehouse_id,transfer_date,expected_arrival_date,status,created_at,updated_at'],
        'sort_direction' => ['nullable', 'string', 'in:asc,desc'],
        'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        'page' => ['nullable', 'integer', 'min:1'],
    ]);
});

