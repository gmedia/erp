<?php

use App\Actions\StockTransfers\ExportStockTransfersAction;
use App\Http\Requests\StockTransfers\ExportStockTransferRequest;
use App\Models\StockTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('stock-transfers');

test('execute generates excel file and returns url', function () {
    Carbon::setTestNow(now());
    Excel::fake();
    Storage::fake('public');

    StockTransfer::factory()->count(3)->create(['status' => 'draft']);

    $action = new ExportStockTransfersAction;
    $request = Mockery::mock(ExportStockTransferRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => null,
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);

    $result = $action->execute($request);

    $filename = 'stock_transfers_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

    expect($result->getStatusCode())->toBe(200)
        ->and($result->getData(true))->toHaveKeys(['url', 'filename'])
        ->and($result->getData(true)['filename'])->toBe($filename);

    Excel::assertStored('exports/' . $filename, 'public');
});
