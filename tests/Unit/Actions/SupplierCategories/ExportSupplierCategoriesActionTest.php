<?php

use App\Actions\SupplierCategories\ExportSupplierCategoriesAction;
use App\Http\Requests\SupplierCategories\ExportSupplierCategoryRequest;
use App\Models\SupplierCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('supplier-categories', 'actions');

test('execute generates excel file and returns url', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');
    
    SupplierCategory::factory()->count(3)->create();

    $action = new ExportSupplierCategoriesAction();
    $request = Mockery::mock(ExportSupplierCategoryRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    
    $result = $action->execute($request);

    $filename = 'supplier_categories_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($result->getStatusCode())->toBe(200)
        ->and($result->getData(true))->toHaveKeys(['url', 'filename'])
        ->and($result->getData(true)['filename'])->toBe($filename);
        
    Excel::assertStored('exports/' . $filename, 'public');
});
