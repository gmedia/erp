<?php

use App\Actions\Accounts\ExportAccountsAction;
use App\Domain\Accounts\AccountFilterService;
use App\Http\Requests\Accounts\ExportAccountRequest;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounts', 'actions');

beforeEach(function () {
    $this->filterService = new AccountFilterService();
    $this->action = new ExportAccountsAction($this->filterService);
});

test('it returns json response with filters', function () {
    $coaVersion = CoaVersion::factory()->create();
    $request = new ExportAccountRequest([
        'coa_version_id' => $coaVersion->id,
        'search' => 'test'
    ]);

    $response = $this->action->execute($request);

    expect($response->getStatusCode())->toBe(200);
    
    $data = json_decode($response->getContent(), true);
    expect($data['message'])->toBe('Export functionality would be implemented here.')
        ->and($data['filters']['search'])->toBe('test');
});
