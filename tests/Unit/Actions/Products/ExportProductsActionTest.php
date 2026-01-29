<?php

use App\Actions\Products\ExportProductsAction;
use App\Http\Requests\Products\ExportProductRequest;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->action = new ExportProductsAction();
});

test('it exports products to excel', function () {
    Excel::fake();
    $now = now();
    \Illuminate\Support\Carbon::setTestNow($now);
    
    Product::factory()->count(5)->create();

    $request = Mockery::mock(ExportProductRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    
    $this->action->execute($request);

    $filename = 'products_export_' . $now->format('Y-m-d_H-i-s') . '.xlsx';
    Excel::assertStored('exports/' . $filename, 'public');
    
    \Illuminate\Support\Carbon::setTestNow(); // Reset time
});
