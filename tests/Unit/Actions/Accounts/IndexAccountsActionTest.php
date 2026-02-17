<?php

use App\Actions\Accounts\IndexAccountsAction;
use App\Domain\Accounts\AccountFilterService;
use App\Http\Requests\Accounts\IndexAccountRequest;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounts');

beforeEach(function () {
    $this->filterService = new AccountFilterService();
    $this->action = new IndexAccountsAction($this->filterService);
});

test('it can index accounts by version', function () {
    $coaVersion = CoaVersion::factory()->create();
    Account::factory()->count(5)->create(['coa_version_id' => $coaVersion->id]);
    Account::factory()->count(3)->create(); // Other versions

    $request = new IndexAccountRequest(['coa_version_id' => $coaVersion->id]);
    
    $result = $this->action->execute($request);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(5);
});

test('it can paginate accounts', function () {
    $coaVersion = CoaVersion::factory()->create();
    Account::factory()->count(20)->create(['coa_version_id' => $coaVersion->id]);

    $request = new IndexAccountRequest([
        'coa_version_id' => $coaVersion->id,
        'per_page' => 10
    ]);
    
    $result = $this->action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class)
        ->and($result->total())->toBe(20)
        ->and($result->items())->toHaveCount(10);
});
