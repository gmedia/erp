<?php

use App\Models\Department;
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

describe('Department API Endpoints', function () {

    beforeEach(function () {
        $user = User::factory()->create();
        actingAs($user);
    });

    test('index returns paginated departments with proper meta structure', function () {
        Department::factory()->count(25)->create();

        $response = getJson('/api/departments?per_page=10');

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

    test('index supports search filtering by department name', function () {
        Department::factory()->create(['name' => 'Marketing Department']);
        Department::factory()->create(['name' => 'Sales Team']);
        Department::factory()->create(['name' => 'Engineering Division']);

        $response = getJson('/api/departments?search=market');

        $response->assertOk();

        $data = $response->json('data');
        expect($data)->toHaveCount(1)
            ->and($data[0]['name'])->toBe('Marketing Department');
    });

    test('index supports sorting by different fields', function () {
        Department::factory()->create(['name' => 'Z Department']);
        Department::factory()->create(['name' => 'A Department']);

        $response = getJson('/api/departments?sort_by=name&sort_direction=asc');

        $response->assertOk();

        $data = $response->json('data');
        expect($data[0]['name'])->toBe('A Department')
            ->and($data[1]['name'])->toBe('Z Department');
    });

    test('store creates department with valid data and returns 201 status', function () {
        $departmentData = [
            'name' => 'Human Resources',
        ];

        $response = postJson('/api/departments', $departmentData);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Human Resources']);

        assertDatabaseHas('departments', ['name' => 'Human Resources']);
    });

    test('store validates required fields', function () {
        $response = postJson('/api/departments', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('show returns single department with full resource structure', function () {
        $department = Department::factory()->create();

        $response = getJson("/api/departments/{$department->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['id' => $department->id, 'name' => $department->name]);
    });

    test('show returns 404 for non-existent department', function () {
        $response = getJson('/api/departments/99999');

        $response->assertNotFound();
    });

    test('update modifies department and returns updated resource', function () {
        $department = Department::factory()->create(['name' => 'Old Name']);
        $updateData = ['name' => 'Updated Department Name'];

        $response = putJson("/api/departments/{$department->id}", $updateData);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ]
            ])
            ->assertJsonFragment(['name' => 'Updated Department Name']);

        $department->refresh();
        expect($department->name)->toBe('Updated Department Name');
    });

    test('update validates name field when provided', function () {
        $department = Department::factory()->create();

        $response = putJson("/api/departments/{$department->id}", ['name' => '']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update returns 404 for non-existent department', function () {
        $response = putJson('/api/departments/99999', ['name' => 'Test']);

        $response->assertNotFound();
    });

    test('destroy removes department and returns 204 status', function () {
        $department = Department::factory()->create();

        $response = deleteJson("/api/departments/{$department->id}");

        $response->assertNoContent();

        assertDatabaseMissing('departments', ['id' => $department->id]);
    });

    test('destroy returns 404 for non-existent department', function () {
        $response = deleteJson('/api/departments/99999');

        $response->assertNotFound();
    });

    test('export generates excel file and returns proper response structure', function () {
        Department::factory()->count(5)->create();

        $response = postJson('/api/departments/export', []);

        $response->assertOk()
            ->assertJsonStructure([
                'url',
                'filename',
            ]);

        $data = $response->json();
        expect($data['url'])->toContain('storage/exports/')
            ->and($data['filename'])->toContain('departments_export_')
            ->and($data['filename'])->toContain('.xlsx')
            ->and($data['filename'])->toMatch('/departments_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/');
    });

    test('export applies search filter correctly', function () {
        Department::factory()->create(['name' => 'Marketing']);
        Department::factory()->create(['name' => 'Sales']);
        Department::factory()->create(['name' => 'Engineering']);

        $response = postJson('/api/departments/export', ['search' => 'market']);

        $response->assertOk();

        // Note: Actual export verification would require checking the generated file
        // This test verifies the endpoint accepts and processes filters
        expect($response->json())->toHaveKeys(['url', 'filename']);
    });

});
