<?php

use App\Domain\AccountMappings\AccountMappingFilterService;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings');

test('AccountMappingFilterService can search notes and account fields', function () {
    $service = new AccountMappingFilterService();

    $sourceVersion = CoaVersion::factory()->create(['status' => 'archived']);
    $targetVersion = CoaVersion::factory()->create(['status' => 'active']);

    $source = Account::factory()->create(['coa_version_id' => $sourceVersion->id, 'code' => '11100', 'name' => 'Cash']);
    $target = Account::factory()->create(['coa_version_id' => $targetVersion->id, 'code' => '11110', 'name' => 'Cash In Bank']);

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
