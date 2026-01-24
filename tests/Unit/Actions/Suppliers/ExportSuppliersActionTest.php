<?php

use App\Actions\Suppliers\ExportSuppliersAction;
use App\Http\Requests\Suppliers\ExportSupplierRequest;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

test('execute exports suppliers and returns file info', function () {
    Storage::fake('local'); // Export action uses 'local' disk explicitly
    Excel::shouldReceive('store')->once();

    $action = new ExportSuppliersAction;

    Supplier::factory()->count(3)->create();

    // Mock request
    $request = Mockery::mock(ExportSupplierRequest::class);
    $request->shouldReceive('validated')->andReturn([]);

    $result = $action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class);

    $data = $result->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toContain('suppliers_')
        ->and($data['filename'])->toContain('.xlsx');
});
