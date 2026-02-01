<?php

use App\Actions\Products\ExportProductsAction;
use App\Http\Requests\Products\ExportProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('products');

test('execute returns json response with url and filename', function () {
    Excel::fake();
    Storage::fake('public');

    $request = Mockery::mock(ExportProductRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'test',
    ]);

    $action = new ExportProductsAction();
    $response = $action->execute($request);

    expect($response)->toBeInstanceOf(JsonResponse::class);
    
    $data = $response->getData(true);
    expect($data)->toHaveKey('url')
        ->and($data)->toHaveKey('filename')
        ->and($data['filename'])->toContain('products_export_');
    
    Excel::assertStored('exports/' . $data['filename'], 'public');
});
