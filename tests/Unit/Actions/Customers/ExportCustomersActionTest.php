<?php

use App\Actions\Customers\ExportCustomersAction;
use App\Http\Requests\Customers\ExportCustomerRequest;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('execute exports customers and returns file info', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportCustomersAction;

    Customer::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportCustomerRequest::class);
    $request->shouldReceive('validated')->andReturn([]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('customers_export_')
        ->and($data['filename'])->toContain('.xlsx');
});

test('execute exports with search filter', function () {
    Storage::fake('public');
    Excel::shouldReceive('store')->once();

    $action = new ExportCustomersAction;

    Customer::factory()->create(['name' => 'John Doe']);
    Customer::factory()->create(['name' => 'Jane Smith']);

    // Mock request with search
    $request = Mockery::mock(ExportCustomerRequest::class);
    $request->shouldReceive('validated')->andReturn(['search' => 'john']);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);
});
