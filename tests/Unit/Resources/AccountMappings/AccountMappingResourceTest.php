<?php

use App\Http\Resources\AccountMappings\AccountMappingResource;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class)->group('account-mappings');

test('AccountMappingResource returns correct fields', function () {
    $sourceVersion = CoaVersion::factory()->create(['name' => 'COA 2025', 'status' => 'archived']);
    $targetVersion = CoaVersion::factory()->create(['name' => 'COA 2026', 'status' => 'active']);

    $source = Account::factory()->create([
        'coa_version_id' => $sourceVersion->id,
        'code' => '11100',
        'name' => 'Cash',
    ]);

    $target = Account::factory()->create([
        'coa_version_id' => $targetVersion->id,
        'code' => '11110',
        'name' => 'Cash In Bank',
    ]);

    $mapping = AccountMapping::create([
        'source_account_id' => $source->id,
        'target_account_id' => $target->id,
        'type' => 'rename',
        'notes' => 'test',
    ]);

    $mapping->load(['sourceAccount.coaVersion', 'targetAccount.coaVersion']);

    $resource = new AccountMappingResource($mapping);
    $data = $resource->toArray(request());

    expect($data)->toHaveKeys([
        'id',
        'source_account_id',
        'target_account_id',
        'type',
        'notes',
        'created_at',
        'updated_at',
        'source_account',
        'target_account',
    ])
        ->and($data['source_account']['code'])->toBe('11100')
        ->and($data['target_account']['code'])->toBe('11110')
        ->and($data['source_account']['coa_version']['name'])->toBe('COA 2025')
        ->and($data['target_account']['coa_version']['name'])->toBe('COA 2026');
});
