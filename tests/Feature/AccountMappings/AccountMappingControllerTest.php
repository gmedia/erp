<?php

use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('account-mappings');

describe('Account Mapping API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'account_mapping',
            'account_mapping.create',
            'account_mapping.edit',
            'account_mapping.delete',
        ]);
        actingAs($user);

        $this->sourceVersion = CoaVersion::factory()->create(['status' => 'archived']);
        $this->targetVersion = CoaVersion::factory()->create(['status' => 'active']);

        $this->sourceAccount = Account::factory()->create([
            'coa_version_id' => $this->sourceVersion->id,
            'code' => '11100',
            'name' => 'Cash',
        ]);

        $this->targetAccount = Account::factory()->create([
            'coa_version_id' => $this->targetVersion->id,
            'code' => '11110',
            'name' => 'Cash In Bank',
        ]);
    });

    test('index returns paginated mappings with nested accounts', function () {
        AccountMapping::create([
            'source_account_id' => $this->sourceAccount->id,
            'target_account_id' => $this->targetAccount->id,
            'type' => 'rename',
            'notes' => 'test',
        ]);

        $response = getJson('/api/account-mappings?per_page=10');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'source_account_id',
                        'target_account_id',
                        'type',
                        'notes',
                        'created_at',
                        'updated_at',
                        'source_account',
                        'target_account',
                    ],
                ],
                'meta',
            ]);

        expect($response->json('data.0.source_account.code'))->toBe('11100')
            ->and($response->json('data.0.target_account.code'))->toBe('11110');
    });

    test('store creates a mapping', function () {
        $payload = [
            'source_account_id' => $this->sourceAccount->id,
            'target_account_id' => $this->targetAccount->id,
            'type' => 'rename',
            'notes' => 'Operating Expense renamed',
        ];

        $response = postJson('/api/account-mappings', $payload);

        $response->assertStatus(201);

        assertDatabaseHas('account_mappings', [
            'source_account_id' => $this->sourceAccount->id,
            'target_account_id' => $this->targetAccount->id,
            'type' => 'rename',
        ]);
    });

    test('store fails when source and target accounts are in same COA version', function () {
        $targetSameVersion = Account::factory()->create([
            'coa_version_id' => $this->sourceVersion->id,
            'code' => '11120',
            'name' => 'Petty Cash',
        ]);

        $payload = [
            'source_account_id' => $this->sourceAccount->id,
            'target_account_id' => $targetSameVersion->id,
            'type' => 'rename',
        ];

        $response = postJson('/api/account-mappings', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['target_account_id']);
    });

    test('update modifies mapping', function () {
        $mapping = AccountMapping::create([
            'source_account_id' => $this->sourceAccount->id,
            'target_account_id' => $this->targetAccount->id,
            'type' => 'rename',
            'notes' => 'before',
        ]);

        $payload = [
            'source_account_id' => $this->sourceAccount->id,
            'target_account_id' => $this->targetAccount->id,
            'type' => 'merge',
            'notes' => 'after',
        ];

        $response = putJson("/api/account-mappings/{$mapping->id}", $payload);

        $response->assertStatus(200);
        assertDatabaseHas('account_mappings', [
            'id' => $mapping->id,
            'type' => 'merge',
            'notes' => 'after',
        ]);
    });

    test('destroy deletes mapping', function () {
        $mapping = AccountMapping::create([
            'source_account_id' => $this->sourceAccount->id,
            'target_account_id' => $this->targetAccount->id,
            'type' => 'rename',
        ]);

        $response = deleteJson("/api/account-mappings/{$mapping->id}");

        $response->assertStatus(204);
        assertDatabaseMissing('account_mappings', ['id' => $mapping->id]);
    });

    test('export returns url and filename', function () {
        Carbon::setTestNow(Carbon::parse('2026-01-01 10:00:00'));
        Excel::fake();
        Storage::fake('public');

        $response = postJson('/api/account-mappings/export', [
            'search' => 'cash',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['url', 'filename']);

        expect($response->json('filename'))->toBe('account_mappings_export_2026-01-01_10-00-00.xlsx');
        Excel::assertStored('exports/account_mappings_export_2026-01-01_10-00-00.xlsx', 'public');
        Carbon::setTestNow();
    });
});
