<?php

use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('positions');

describe('Position API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'position',
            'position.create',
            'position.edit',
            'position.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated positions', function () {
        // User creation creates 1 position via EmployeeFactory
        // We create 14 more to reach 15 total
        Position::factory()->count(14)->create();

        $response = getJson('/api/positions?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'created_at', 'updated_at']
                ],
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        Position::factory()->create(['name' => 'Manager']);
        Position::factory()->create(['name' => 'Staff']);

        $response = getJson('/api/positions?search=Man');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Manager');
    });

    test('index sorts results', function () {
        Position::factory()->create(['name' => 'AAAA Alpha']);
        Position::factory()->create(['name' => 'ZZZZ Beta']);

        // Sort Descending -> ZZZZ Beta should be first
        $response = getJson('/api/positions?sort_by=name&sort_direction=desc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('ZZZZ Beta');
        
        // Sort Ascending -> AAAA Alpha should be first
        $response = getJson('/api/positions?sort_by=name&sort_direction=asc');
        
        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('AAAA Alpha');
    });

    test('store creates position', function () {
        $data = [
            'name' => 'New Position',
        ];

        $response = postJson('/api/positions', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Position']);

        assertDatabaseHas('positions', ['name' => 'New Position']);
    });

    test('store validates unique name', function () {
        Position::factory()->create(['name' => 'Existing Pos']);

        $response = postJson('/api/positions', [
            'name' => 'Existing Pos',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update modifies position', function () {
        $position = Position::factory()->create();
        $data = [
            'name' => 'Updated Pos',
        ];

        $response = putJson("/api/positions/{$position->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Pos']);

        assertDatabaseHas('positions', ['id' => $position->id, 'name' => 'Updated Pos']);
    });

    test('destroy removes position', function () {
        $position = Position::factory()->create();

        $response = deleteJson("/api/positions/{$position->id}");

        $response->assertNoContent();
        assertDatabaseMissing('positions', ['id' => $position->id]);
    });
});
