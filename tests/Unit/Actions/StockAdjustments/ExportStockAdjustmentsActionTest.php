<?php

use App\Actions\StockAdjustments\ExportStockAdjustmentsAction;
use App\Http\Requests\StockAdjustments\ExportStockAdjustmentRequest;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('stock-adjustments');

test('execute generates excel file and returns url', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');

    StockAdjustment::factory()->count(3)->create(['status' => 'draft']);

    $action = new ExportStockAdjustmentsAction;
    $request = Mockery::mock(ExportStockAdjustmentRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);

    $result = $action->execute($request);

    $filename = 'stock_adjustments_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($result->getStatusCode())->toBe(200)
        ->and($result->getData(true))->toHaveKeys(['url', 'filename'])
        ->and($result->getData(true)['filename'])->toBe($filename);

    Excel::assertStored('exports/' . $filename, 'public');
});
