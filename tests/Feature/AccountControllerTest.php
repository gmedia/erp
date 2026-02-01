<?php

use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('accounts');

describe('Account API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'coa_version', // Usually accounts are managed under coa_version permission
        ]);
        actingAs($user);
        
        $this->coaVersion = CoaVersion::factory()->create();
    });

    test('index returns accounts for a coa version', function () {
        Account::factory()->count(10)->create([
            'coa_version_id' => $this->coaVersion->id,
        ]);

        $response = getJson("/api/accounts?coa_version_id={$this->coaVersion->id}");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'coa_version_id',
                        'parent_id',
                        'code',
                        'name',
                        'type',
                        'sub_type',
                        'normal_balance',
                        'level',
                        'is_active',
                        'is_cash_flow',
                        'description',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta',
            ]);
    });

    test('index filters by search code or name', function () {
        Account::factory()->create(['code' => '11000', 'name' => 'Cash', 'coa_version_id' => $this->coaVersion->id]);
        Account::factory()->create(['code' => '12000', 'name' => 'Bank', 'coa_version_id' => $this->coaVersion->id]);

        $response = getJson("/api/accounts?coa_version_id={$this->coaVersion->id}&search=Cash");
        $response->assertJsonCount(1, 'data');
        expect($response->json('data.0.code'))->toBe('11000');

        $response = getJson("/api/accounts?coa_version_id={$this->coaVersion->id}&search=12000");
        $response->assertJsonCount(1, 'data');
        expect($response->json('data.0.name'))->toBe('Bank');
    });

    test('store creates a new account', function () {
        $payload = [
            'coa_version_id' => $this->coaVersion->id,
            'code' => '11100',
            'name' => 'Petty Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'level' => 1,
            'is_active' => true,
        ];

        $response = postJson('/api/accounts', $payload);

        $response->assertStatus(201);
        assertDatabaseHas('accounts', [
            'code' => '11100',
            'name' => 'Petty Cash',
        ]);
    });

    test('store fails with duplicate code in same version', function () {
        Account::factory()->create(['code' => '11100', 'coa_version_id' => $this->coaVersion->id]);

        $payload = [
            'coa_version_id' => $this->coaVersion->id,
            'code' => '11100',
            'name' => 'Should Fail',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'level' => 1,
        ];

        $response = postJson('/api/accounts', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    });

    test('update modifies existing account', function () {
        $account = Account::factory()->create(['coa_version_id' => $this->coaVersion->id]);

        $payload = [
            'coa_version_id' => $this->coaVersion->id,
            'code' => 'UPDATED',
            'name' => 'Updated Name',
            'type' => 'liability',
            'normal_balance' => 'credit',
            'level' => 2,
        ];

        $response = putJson("/api/accounts/{$account->id}", $payload);

        $response->assertStatus(200);
        assertDatabaseHas('accounts', [
            'id' => $account->id,
            'code' => 'UPDATED',
            'name' => 'Updated Name',
        ]);
    });

    test('destroy deletes account without dependencies', function () {
        $account = Account::factory()->create(['coa_version_id' => $this->coaVersion->id]);

        $response = deleteJson("/api/accounts/{$account->id}");

        $response->assertStatus(204);
        assertDatabaseMissing('accounts', ['id' => $account->id]);
    });

    test('destroy fails if account has children', function () {
        $parent = Account::factory()->create(['coa_version_id' => $this->coaVersion->id]);
        Account::factory()->create([
            'parent_id' => $parent->id,
            'coa_version_id' => $this->coaVersion->id,
        ]);

        $response = deleteJson("/api/accounts/{$parent->id}");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot delete account with child accounts.');
    });

    test('export returns message and filters', function () {
        $response = postJson('/api/accounts/export', [
            'coa_version_id' => $this->coaVersion->id,
            'search' => 'cash',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'filters']);
    });
});
