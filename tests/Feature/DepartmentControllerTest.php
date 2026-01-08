<?php

use App\Models\Department;
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

describe('CRUD API Endpoints', function () {

    dataset('crud_models', [
        [Department::class, 'departments', 'departments', 'Department'],
        [Position::class, 'positions', 'positions', 'Position'],
    ]);

    beforeEach(function () {
        $user = User::factory()->create();
        actingAs($user);
    });

    test('{3} index returns paginated items with proper meta structure', function ($model, $route, $table, $name) {
        $model::factory()->count(25)->create();

        $response = getJson("/api/{$route}?per_page=10");

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
    })->with('crud_models');

    test('{3} index supports search filtering by name', function ($model, $route, $table, $name) {
        $model::factory()->create(['name' => 'Marketing ' . $name]);
        $model::factory()->create(['name' => 'Sales ' . $name]);
        $model::factory()->create(['name' => 'Engineering ' . $name]);

        $response = getJson("/api/{$route}?search=market");

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['name'])->toBe('Marketing ' . $name);
    })->with('crud_models');

    test('{3} index supports sorting by different fields', function ($model, $route, $table, $name) {
        $model::factory()->create(['name' => 'Z ' . $name]);
        $model::factory()->create(['name' => 'A ' . $name]);

        $response = getJson("/api/{$route}?sort_by=name&sort_direction=asc");

        $response->assertOk();

        $data = $response->json('data');
        expect($data[0]['name'])->toBe('A ' . $name)
            ->and($data[1]['name'])->toBe('Z ' . $name);
    })->with('crud_models');

    test('{3} store creates item with valid data and returns 201 status', function ($model, $route, $table, $name) {
        $data = [
            'name' => 'Test ' . $name,
        ];

        $response = postJson("/api/{$route}", $data);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Test ' . $name]);

        assertDatabaseHas($table, ['name' => 'Test ' . $name]);
    })->with('crud_models');

    test('{3} store validates required fields', function ($model, $route, $table, $name) {
        $response = postJson("/api/{$route}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    })->with('crud_models');

    test('{3} show returns single item with full resource structure', function ($model, $route, $table, $name) {
        $item = $model::factory()->create();

        $response = getJson("/api/{$route}/{$item->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['id' => $item->id, 'name' => $item->name]);
    })->with('crud_models');

    test('{3} show returns 404 for non-existent item', function ($model, $route, $table, $name) {
        $response = getJson("/api/{$route}/99999");

        $response->assertNotFound();
    })->with('crud_models');

    test('{3} update modifies item and returns updated resource', function ($model, $route, $table, $name) {
        $item = $model::factory()->create(['name' => 'Old Name']);
        $updateData = ['name' => 'Updated ' . $name . ' Name'];

        $response = putJson("/api/{$route}/{$item->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Updated ' . $name . ' Name']);

        $item->refresh();
        expect($item->name)->toBe('Updated ' . $name . ' Name');
    })->with('crud_models');

    test('{3} update validates name field when provided', function ($model, $route, $table, $name) {
        $item = $model::factory()->create();

        $response = putJson("/api/{$route}/{$item->id}", ['name' => '']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    })->with('crud_models');

    test('{3} update returns 404 for non-existent item', function ($model, $route, $table, $name) {
        $response = putJson("/api/{$route}/99999", ['name' => 'Test']);

        $response->assertNotFound();
    })->with('crud_models');

    test('{3} destroy removes item and returns 204 status', function ($model, $route, $table, $name) {
        $item = $model::factory()->create();

        $response = deleteJson("/api/{$route}/{$item->id}");

        $response->assertNoContent();

        assertDatabaseMissing($table, ['id' => $item->id]);
    })->with('crud_models');

    test('{3} destroy returns 404 for non-existent item', function ($model, $route, $table, $name) {
        $response = deleteJson("/api/{$route}/99999");

        $response->assertNotFound();
    })->with('crud_models');

    test('{3} export generates excel file and returns proper response structure', function ($model, $route, $table, $name) {
        $model::factory()->count(5)->create();

        $response = postJson("/api/{$route}/export", []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toContain('storage/exports/')
            ->and($data['filename'])->toContain("{$route}_export_")
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch("/{$route}_export_\\d{4}-\\d{2}-\\d{2}_\\d{2}-\\d{2}-\\d{2}\\.xlsx/");
    })->with('crud_models');

    test('{3} export applies search filter correctly', function ($model, $route, $table, $name) {
        $model::factory()->create(['name' => 'Manager']);
        $model::factory()->create(['name' => 'Developer']);
        $model::factory()->create(['name' => 'Designer']);

        $response = postJson("/api/{$route}/export", ['search' => 'dev']);

        $response->assertOk();

        // Note: Actual export verification would require checking the generated file
        // This test verifies the endpoint accepts and processes filters
        expect($response->json())->toHaveKeys(['url', 'filename']);
    })->with('crud_models');

});
