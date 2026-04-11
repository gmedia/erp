<?php

use App\Actions\Accounts\IndexAccountsAction;
use App\Domain\Accounts\AccountFilterService;
use App\Http\Requests\Accounts\IndexAccountRequest;
use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('accounts');

beforeEach(function () {
    $this->filterService = new AccountFilterService;
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

test('it defaults to the active coa version when no version is provided', function () {
    $activeVersion = CoaVersion::factory()->create(['status' => 'active']);
    $draftVersion = CoaVersion::factory()->create(['status' => 'draft']);

    Account::factory()->count(4)->create(['coa_version_id' => $activeVersion->id]);
    Account::factory()->count(2)->create(['coa_version_id' => $draftVersion->id]);

    $result = $this->action->execute(new IndexAccountRequest);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(4)
        ->and($result->pluck('coa_version_id')->unique()->all())->toBe([$activeVersion->id]);
});

test('it can paginate accounts', function () {
    $coaVersion = CoaVersion::factory()->create();
    Account::factory()->count(20)->create(['coa_version_id' => $coaVersion->id]);

    $request = new IndexAccountRequest([
        'coa_version_id' => $coaVersion->id,
        'per_page' => 10,
    ]);

    $result = $this->action->execute($request);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result->total())->toBe(20)
        ->and($result->items())->toHaveCount(10);
});
