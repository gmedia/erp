<?php

use App\Models\Branch;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('branches');

describe('Branch API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'branch',
            'branch.create',
            'branch.edit',
            'branch.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated branches', function () {
        $baseline = Branch::count();
        Branch::factory()->count(15)->create();

        $response = getJson('/api/branches?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'created_at', 'updated_at']
                ],
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe($baseline + 15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        Branch::factory()->create(['name' => 'Main Office']);
        Branch::factory()->create(['name' => 'Warehouse']);

        $response = getJson('/api/branches?search=Main');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Main Office');
    });

    test('index sorts results', function () {
        // Ensure the automatically created branch doesn't interfere with first/last checks
        // We can just create names that we know will be at the extremes
        
        Branch::factory()->create(['name' => 'AAAA Alpha']);
        Branch::factory()->create(['name' => 'ZZZZ Beta']);

        // Sort Descending -> ZZZZ Beta should be first
        $response = getJson('/api/branches?sort_by=name&sort_direction=desc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('ZZZZ Beta');
        
        // Sort Ascending -> AAAA Alpha should be first
        $response = getJson('/api/branches?sort_by=name&sort_direction=asc');
        
        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('AAAA Alpha');
    });

    test('store creates branch', function () {
        $data = [
            'name' => 'New Branch',
        ];

        $response = postJson('/api/branches', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Branch']);

        assertDatabaseHas('branches', ['name' => 'New Branch']);
    });

    test('store validates unique name', function () {
        Branch::factory()->create(['name' => 'Existing Branch']);

        $response = postJson('/api/branches', [
            'name' => 'Existing Branch',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update modifies branch', function () {
        $branch = Branch::factory()->create();
        $data = [
            'name' => 'Updated Branch',
        ];

        $response = putJson("/api/branches/{$branch->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Branch']);

        assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Updated Branch']);
    });

    test('destroy removes branch', function () {
        $branch = Branch::factory()->create();

        $response = deleteJson("/api/branches/{$branch->id}");

        $response->assertNoContent();
        assertDatabaseMissing('branches', ['id' => $branch->id]);
    });
});
