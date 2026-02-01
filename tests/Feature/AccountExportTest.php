<?php

use App\Models\CoaVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class)->group('accounts', 'export');

describe('Account Export API', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions(['coa_version']);
        actingAs($user);
        $this->coaVersion = CoaVersion::factory()->create();
    });

    test('export endpoint returns success message', function () {
        $response = postJson('/api/accounts/export', [
            'coa_version_id' => $this->coaVersion->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'filters']);
        
        expect($response->json('message'))->toBe('Export functionality would be implemented here.');
    });
});
