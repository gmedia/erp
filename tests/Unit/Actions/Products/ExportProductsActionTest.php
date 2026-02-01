<?php
 
namespace Tests\Unit\Actions\Products;
 
use App\Actions\Products\ExportProductsAction;
use App\Http\Requests\Products\ExportProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use ReflectionClass;
 
uses(RefreshDatabase::class)->group('products');
 
beforeEach(function () {
    $this->action = new ExportProductsAction();
});
 
test('it exports products to excel with filters', function () {
    Excel::fake();
    $now = now();
    \Illuminate\Support\Carbon::setTestNow($now);
    
    $filters = [
        'category_id' => 1,
        'status' => 'active',
        'search' => 'test'
    ];

    $request = Mockery::mock(ExportProductRequest::class);
    $request->shouldReceive('validated')->andReturn($filters);
    
    $this->action->execute($request);

    $filename = 'products_export_' . $now->format('Y-m-d_H-i-s') . '.xlsx';
    Excel::assertStored('exports/' . $filename, 'public');
    
    Excel::assertStored('exports/' . $filename, 'public', function (\App\Exports\ProductExport $export) use ($filters) {
        $reflection = new ReflectionClass($export);
        $property = $reflection->getProperty('filters');
        $property->setAccessible(true);
        $actualFilters = $property->getValue($export);
        
        return ($actualFilters['category_id'] ?? null) === $filters['category_id'] &&
               ($actualFilters['status'] ?? null) === $filters['status'] &&
               ($actualFilters['search'] ?? null) === $filters['search'];
    });
    
    \Illuminate\Support\Carbon::setTestNow(); // Reset time
});
