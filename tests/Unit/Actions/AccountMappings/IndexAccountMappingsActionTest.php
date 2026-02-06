<?php

use App\Actions\AccountMappings\IndexAccountMappingsAction;
use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Http\Requests\AccountMappings\IndexAccountMappingRequest;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings', 'actions');

beforeEach(function () {
    $this->filterService = new AccountMappingFilterService();
    $this->action = new IndexAccountMappingsAction($this->filterService);
});

test('it paginates and filters by type', function () {
    $sourceVersion = CoaVersion::factory()->create(['status' => 'archived']);
    $targetVersion = CoaVersion::factory()->create(['status' => 'active']);

    $source = Account::factory()->create(['coa_version_id' => $sourceVersion->id, 'code' => '11100', 'name' => 'Cash']);
    $target = Account::factory()->create(['coa_version_id' => $targetVersion->id, 'code' => '11110', 'name' => 'Cash In Bank']);

    AccountMapping::create([
        'source_account_id' => $source->id,
        'target_account_id' => $target->id,
        'type' => 'rename',
        'notes' => 'one',
    ]);

    AccountMapping::create([
        'source_account_id' => $source->id,
        'target_account_id' => $target->id,
        'type' => 'merge',
        'notes' => 'two',
    ]);

    $request = new IndexAccountMappingRequest([
        'type' => 'rename',
        'per_page' => 10,
    ]);

    $result = $this->action->execute($request);

    expect($result)->toBeInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class)
        ->and($result->total())->toBe(1)
        ->and($result->items()[0]->type)->toBe('rename');
});
