<?php

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('units');

describe('Unit API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'unit',
            'unit.create',
            'unit.edit',
            'unit.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated units', function () {
        Unit::factory()->count(15)->create();

        $response = getJson('/api/units?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'symbol', 'created_at', 'updated_at']
                ],
                'meta' => ['total', 'per_page', 'current_page']
            ]);

        expect($response->json('meta.total'))->toBe(15)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering', function () {
        Unit::factory()->create(['name' => 'Kilogram']);
        Unit::factory()->create(['name' => 'Meter']);

        $response = getJson('/api/units?search=Kilo');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('Kilogram');
    });

    test('index sorts results', function () {
        Unit::factory()->create(['name' => 'AAAA Alpha']);
        Unit::factory()->create(['name' => 'ZZZZ Beta']);

        $response = getJson('/api/units?sort_by=name&sort_direction=desc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('ZZZZ Beta');
        
        $response = getJson('/api/units?sort_by=name&sort_direction=asc');
        
        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('AAAA Alpha');
    });

    test('store creates unit', function () {
        $data = [
            'name' => 'Kilogram',
            'symbol' => 'kg'
        ];

        $response = postJson('/api/units', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Kilogram', 'symbol' => 'kg']);

        assertDatabaseHas('units', ['name' => 'Kilogram', 'symbol' => 'kg']);
    });

    test('store validates unique name', function () {
        Unit::factory()->create(['name' => 'Existing Unit']);

        $response = postJson('/api/units', [
            'name' => 'Existing Unit',
            'symbol' => 'EU'
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update modifies unit', function () {
        $unit = Unit::factory()->create();
        $data = [
            'name' => 'Updated Unit',
            'symbol' => 'UU'
        ];

        $response = putJson("/api/units/{$unit->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Unit', 'symbol' => 'UU']);

        assertDatabaseHas('units', ['id' => $unit->id, 'name' => 'Updated Unit']);
    });

    test('destroy removes unit', function () {
        $unit = Unit::factory()->create();

        $response = deleteJson("/api/units/{$unit->id}");

        $response->assertNoContent();
        assertDatabaseMissing('units', ['id' => $unit->id]);
    });
});
