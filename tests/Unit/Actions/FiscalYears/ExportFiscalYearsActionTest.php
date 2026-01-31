<?php

use App\Actions\FiscalYears\ExportFiscalYearsAction;
use App\Http\Requests\FiscalYears\ExportFiscalYearRequest;
use App\Models\FiscalYear;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Exports\FiscalYearExport;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('fiscal-years');

test('execute returns export response', function () {
    Excel::fake();
    Illuminate\Support\Carbon::setTestNow('2026-01-31 12:00:00');
    
    $action = new ExportFiscalYearsAction();

    $request = Mockery::mock(ExportFiscalYearRequest::class);
    $request->shouldReceive('validated')->andReturn([]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('filled')->with('status')->andReturn(false);
    $request->shouldReceive('get')->with('search')->andReturn(null);
    $request->shouldReceive('get')->with('status')->andReturn(null);
    $request->shouldReceive('get')->with('sort_by', 'created_at')->andReturn('created_at');
    $request->shouldReceive('get')->with('sort_direction', 'desc')->andReturn('desc');

    $result = $action->execute($request);
    $data = $result->getData(true);

    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toBe('fiscal_years_export_2026-01-31_12-00-00.xlsx');
        
    Excel::assertStored('exports/fiscal_years_export_2026-01-31_12-00-00.xlsx', 'public');
});
