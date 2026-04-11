<?php

use App\Actions\AccountMappings\ExportAccountMappingsAction;
use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Http\Requests\AccountMappings\ExportAccountMappingRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('account-mappings');

test('execute stores export file and returns json response', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));
    Excel::fake();
    Storage::fake('public');

    $request = Mockery::mock(ExportAccountMappingRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'search' => 'cash',
        'type' => 'rename',
    ]);

    $action = new ExportAccountMappingsAction(new AccountMappingFilterService);
    $response = $action->execute($request);

    expect($response->getStatusCode())->toBe(200);

    $data = $response->getData(true);
    expect($data)->toHaveKeys(['url', 'filename']);
    expect($data['filename'])->toBe('account_mappings_export_2026-01-01_10-00-00.xlsx');

    Excel::assertStored('exports/' . $data['filename'], 'public');

    Carbon::setTestNow();
});
