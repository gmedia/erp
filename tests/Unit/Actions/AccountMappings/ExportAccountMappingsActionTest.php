<?php

use App\Actions\AccountMappings\ExportAccountMappingsAction;
use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Http\Requests\AccountMappings\ExportAccountMappingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('account-mappings', 'actions');

test('execute stores export file and returns json response', function () {
    Excel::fake();
    Storage::fake('public');

    $request = Mockery::mock(ExportAccountMappingRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'cash',
        'type' => 'rename',
    ]);
    $request->shouldReceive('filled')->with('search')->andReturn(true);

    $action = new ExportAccountMappingsAction(new AccountMappingFilterService());
    $response = $action->execute($request);

    expect($response->getStatusCode())->toBe(200);

    $data = $response->getData(true);
    expect($data)->toHaveKeys(['url', 'filename']);

    Excel::assertStored('exports/' . $data['filename'], 'public');
});

