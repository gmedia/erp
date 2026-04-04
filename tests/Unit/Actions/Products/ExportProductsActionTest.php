<?php

use App\Actions\Products\ExportProductsAction;
use App\Http\Requests\Products\ExportProductRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('products');

test('execute returns json response with url and filename', function () {
    Carbon::setTestNow('2026-04-04 11:10:00');
    Excel::fake();
    Storage::fake('public');

    $request = Mockery::mock(ExportProductRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'test',
    ]);

    $action = new ExportProductsAction;
    $response = $action->execute($request);

    expect($response)->toBeInstanceOf(JsonResponse::class);

    $data = $response->getData(true);
    expect($data)->toHaveKey('url')
        ->and($data)->toHaveKey('filename')
        ->and($data['filename'])->toBe('products_export_2026-04-04_11-10-00.xlsx');

    Excel::assertStored('exports/' . $data['filename'], 'public');
    Carbon::setTestNow();
});
