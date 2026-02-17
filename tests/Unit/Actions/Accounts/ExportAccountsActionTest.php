<?php

use App\Actions\Accounts\ExportAccountsAction;
use App\Http\Requests\Accounts\ExportAccountRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('accounts');

test('execute stores export file and returns json response', function () {
    Excel::fake();
    Storage::fake('public');

    $coaVersion = CoaVersion::factory()->create();

    $request = Mockery::mock(ExportAccountRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'coa_version_id' => $coaVersion->id,
        'search' => 'test',
    ]);

    $action = new ExportAccountsAction();
    $response = $action->execute($request);

    expect($response->getStatusCode())->toBe(200);
    
    $data = $response->getData(true);
    expect($data)->toHaveKeys(['url', 'filename']);

    Excel::assertStored('exports/' . $data['filename'], 'public');
});
