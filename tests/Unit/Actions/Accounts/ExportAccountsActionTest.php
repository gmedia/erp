<?php

use App\Actions\Accounts\ExportAccountsAction;
use App\Http\Requests\Accounts\ExportAccountRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('accounts');

test('execute stores export file and returns json response', function () {
    Carbon::setTestNow('2026-04-04 11:05:00');
    Excel::fake();
    Storage::fake('public');

    $coaVersion = CoaVersion::factory()->create();

    $request = Mockery::mock(ExportAccountRequest::class);
    $request->shouldReceive('validated')->andReturn([
        'coa_version_id' => $coaVersion->id,
        'search' => 'test',
    ]);

    $action = new ExportAccountsAction;
    $response = $action->execute($request);

    expect($response->getStatusCode())->toBe(200);

    $data = $response->getData(true);
    expect($data)->toHaveKeys(['url', 'filename'])
        ->and($data['filename'])->toBe('accounts_export_2026-04-04_11-05-00.xlsx');

    Excel::assertStored('exports/' . $data['filename'], 'public');
    Carbon::setTestNow();
});
