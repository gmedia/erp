<?php

use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings');

test('AccountMappingFilterService filters by type', function () {
    $service = new AccountMappingFilterService;

    /** @var Account $source */
    $source = Account::factory()->create();
    /** @var Account $target */
    $target = Account::factory()->create();

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

    $query = AccountMapping::query();
    $service->applyAdvancedFilters($query, ['type' => 'rename']);

    expect($query->count())->toBe(1)
        ->and($query->first()->type)->toBe('rename');
});

test('AccountMappingFilterService filters by source coa version', function () {
    $service = new AccountMappingFilterService;
    /** @var CoaVersion $sourceVersionA */
    $sourceVersionA = CoaVersion::factory()->create();
    /** @var CoaVersion $sourceVersionB */
    $sourceVersionB = CoaVersion::factory()->create();
    /** @var CoaVersion $targetVersion */
    $targetVersion = CoaVersion::factory()->create();

    /** @var Account $sourceA */
    $sourceA = Account::factory()->create(['coa_version_id' => $sourceVersionA->id]);
    /** @var Account $sourceB */
    $sourceB = Account::factory()->create(['coa_version_id' => $sourceVersionB->id]);
    /** @var Account $target */
    $target = Account::factory()->create(['coa_version_id' => $targetVersion->id]);

    AccountMapping::create([
        'source_account_id' => $sourceA->id,
        'target_account_id' => $target->id,
        'type' => 'rename',
        'notes' => 'one',
    ]);

    AccountMapping::create([
        'source_account_id' => $sourceB->id,
        'target_account_id' => $target->id,
        'type' => 'rename',
        'notes' => 'two',
    ]);

    $query = AccountMapping::query();
    $service->applyAdvancedFilters($query, ['source_coa_version_id' => $sourceVersionA->id]);

    expect($query->count())->toBe(1)
        ->and($query->first()->sourceAccount->coa_version_id)->toBe($sourceVersionA->id);
});

test('AccountMappingFilterService filters by target coa version', function () {
    $service = new AccountMappingFilterService;
    /** @var CoaVersion $sourceVersion */
    $sourceVersion = CoaVersion::factory()->create();
    /** @var CoaVersion $targetVersionA */
    $targetVersionA = CoaVersion::factory()->create();
    /** @var CoaVersion $targetVersionB */
    $targetVersionB = CoaVersion::factory()->create();

    /** @var Account $source */
    $source = Account::factory()->create(['coa_version_id' => $sourceVersion->id]);
    /** @var Account $targetA */
    $targetA = Account::factory()->create(['coa_version_id' => $targetVersionA->id]);
    /** @var Account $targetB */
    $targetB = Account::factory()->create(['coa_version_id' => $targetVersionB->id]);

    AccountMapping::create([
        'source_account_id' => $source->id,
        'target_account_id' => $targetA->id,
        'type' => 'rename',
        'notes' => 'one',
    ]);

    AccountMapping::create([
        'source_account_id' => $source->id,
        'target_account_id' => $targetB->id,
        'type' => 'rename',
        'notes' => 'two',
    ]);

    $query = AccountMapping::query();
    $service->applyAdvancedFilters($query, ['target_coa_version_id' => $targetVersionA->id]);

    expect($query->count())->toBe(1)
        ->and($query->first()->targetAccount->coa_version_id)->toBe($targetVersionA->id);
});

test('AccountMappingFilterService can search notes and account fields', function () {
    $service = new AccountMappingFilterService;

    /** @var CoaVersion $sourceVersion */
    $sourceVersion = CoaVersion::factory()->create(['status' => 'archived']);
    /** @var CoaVersion $targetVersion */
    $targetVersion = CoaVersion::factory()->create(['status' => 'active']);

    /** @var Account $source */
    $source = Account::factory()->create(['coa_version_id' => $sourceVersion->id, 'code' => '11100', 'name' => 'Cash']);
    /** @var Account $target */
    $target = Account::factory()->create([
        'coa_version_id' => $targetVersion->id,
        'code' => '11110',
        'name' => 'Cash In Bank',
    ]);

    AccountMapping::create([
        'source_account_id' => $source->id,
        'target_account_id' => $target->id,
        'type' => 'rename',
        'notes' => 'special note',
    ]);

    AccountMapping::create([
        'source_account_id' => $source->id,
        'target_account_id' => $target->id,
        'type' => 'rename',
        'notes' => 'other',
    ]);

    $query = AccountMapping::query();
    $service->applySearch($query, 'special');

    expect($query->count())->toBe(1);
});
