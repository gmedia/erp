<?php

use App\Actions\CoaVersions\ExportCoaVersionsAction;
use App\Models\CoaVersion;
use App\Http\Requests\CoaVersions\ExportCoaVersionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('coa-versions');

test('execute stores export file and returns json response', function () {
    Excel::fake();
    Storage::fake('public');

    CoaVersion::factory()->count(3)->create();

    $request = Mockery::mock(ExportCoaVersionRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'sort_by' => 'created_at',
        'sort_direction' => 'desc',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(false);
    $request->shouldReceive('filled')->with('status')->andReturn(false);
    $request->shouldReceive('filled')->with('fiscal_year_id')->andReturn(false);

    $action = new ExportCoaVersionsAction();
    $response = $action->execute($request);

    expect($response->getStatusCode())->toBe(200);
    $data = $response->getData(true);
    expect($data)->toHaveKeys(['url', 'filename']);
    
    Excel::assertStored('exports/' . $data['filename'], 'public');
});
