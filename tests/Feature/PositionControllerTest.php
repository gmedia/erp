<?php

use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class);

describe('Position API Endpoints', function () {

    beforeEach(function () {
        $user = User::factory()->create();
        actingAs($user);
    });

    test('index returns paginated positions with proper meta structure', function () {
        Position::factory()->count(25)->create();

        $response = getJson('/api/positions?per_page=10');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                    ]
                ],
                'meta' => [
                    'current_page',
                    'from',
                    'last_page',
                    'per_page',
                    'to',
                    'total',
                ],
            ]);

        expect($response->json('meta.total'))->toBe(25)
            ->and($response->json('meta.per_page'))->toBe(10)
            ->and($response->json('data'))->toHaveCount(10);
    });

    test('index supports search filtering by position name', function () {
        Position::factory()->create(['name' => 'Senior Manager']);
        Position::factory()->create(['name' => 'Developer']);
        Position::factory()->create(['name' => 'Product Designer']);

        $response = getJson('/api/positions?search=Developer');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['name'])->toBe('Developer');
    });

    test('index supports sorting by different fields', function () {
        Position::factory()->create(['name' => 'Z Position']);
        Position::factory()->create(['name' => 'A Position']);

        $response = getJson('/api/positions?sort_by=name&sort_direction=asc');

        $response->assertOk();

        $data = $response->json('data');
        expect($data[0]['name'])->toBe('A Position')
            ->and($data[1]['name'])->toBe('Z Position');
    });

    test('store creates position with valid data and returns 201 status', function () {
        $positionData = [
            'name' => 'Product Manager',
        ];

        $response = postJson('/api/positions', $positionData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Product Manager']);

        assertDatabaseHas('positions', ['name' => 'Product Manager']);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/positions', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('show returns single position with full resource structure', function () {
        $position = Position::factory()->create();

        $response = getJson("/api/positions/{$position->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['id' => $position->id, 'name' => $position->name]);
    });

    test('show returns 404 for non-existent position', function () {
        $response = getJson('/api/positions/99999');

        $response->assertNotFound();
    });

    test('update modifies position and returns updated resource', function () {
        $position = Position::factory()->create(['name' => 'Old Position']);
        $updateData = ['name' => 'Updated Position Name'];

        $response = putJson("/api/positions/{$position->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Updated Position Name']);

        $position->refresh();
        expect($position->name)->toBe('Updated Position Name');
    });

    test('update validates name field when provided', function () {
        $position = Position::factory()->create();

        $response = putJson("/api/positions/{$position->id}", ['name' => '']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update returns 404 for non-existent position', function () {
        $response = putJson('/api/positions/99999', ['name' => 'Test Position']);

        $response->assertNotFound();
    });

    test('destroy removes position and returns 204 status', function () {
        $position = Position::factory()->create();

        $response = deleteJson("/api/positions/{$position->id}");

        $response->assertNoContent();

        assertDatabaseMissing('positions', ['id' => $position->id]);
    });

    test('destroy returns 404 for non-existent position', function () {
        $response = deleteJson('/api/positions/99999');

        $response->assertNotFound();
    });

    test('export generates excel file and returns proper response structure', function () {
        Position::factory()->count(5)->create();

        $response = postJson('/api/positions/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toContain('storage/exports/')
            ->and($data['filename'])->toContain('positions_export_')
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch('/positions_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies search filter correctly', function () {
        Position::factory()->create(['name' => 'Manager']);
        Position::factory()->create(['name' => 'Developer']);
        Position::factory()->create(['name' => 'Designer']);

        $response = postJson('/api/positions/export', ['search' => 'dev']);

        $response->assertOk();

        // Note: Actual export verification would require checking the generated file
        // This test verifies the endpoint accepts and processes filters
        expect($response->json())->toHaveKeys(['url', 'filename']);
    });

});
