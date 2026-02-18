<?php

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(RefreshDatabase::class)->group('departments');

describe('Department API Endpoints', function () {
    beforeEach(function () {
        $user = createTestUserWithPermissions([
            'department',
            'department.create',
            'department.edit',
            'department.delete'
        ]);

        actingAs($user);
    });

    test('index returns paginated departments', function () {
        // User creation creates 1 (or more) departments via EmployeeFactory side effects
        // We dynamically calculate how many more we need to reach 15 total
        $existingCount = Department::count();
        $needed = 15 - $existingCount;
        
        if ($needed > 0) {
            Department::factory()->count($needed)->create();
        }

        $response = getJson('/api/departments?per_page=10');

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
        Department::query()->delete();

        Department::factory()->create(['name' => 'XQZFIND Dept']);
        Department::factory()->create(['name' => 'HR Dept']);

        $response = getJson('/api/departments?search=XQZFIND');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(1)
            ->and($response->json('data.0.name'))->toBe('XQZFIND Dept');
    });

    test('index sorts results', function () {
        Department::factory()->create(['name' => 'AAAA Alpha']);
        Department::factory()->create(['name' => 'ZZZZ Beta']);

        // Sort Descending -> ZZZZ Beta should be first
        $response = getJson('/api/departments?sort_by=name&sort_direction=desc');

        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('ZZZZ Beta');
        
        // Sort Ascending -> AAAA Alpha should be first
        $response = getJson('/api/departments?sort_by=name&sort_direction=asc');
        
        $response->assertOk();
        expect($response->json('data.0.name'))->toBe('AAAA Alpha');
    });

    test('store creates department', function () {
        $data = [
            'name' => 'New Department',
        ];

        $response = postJson('/api/departments', $data);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'New Department']);

        assertDatabaseHas('departments', ['name' => 'New Department']);
    });

    test('store validates unique name', function () {
        Department::factory()->create(['name' => 'Existing Dept']);

        $response = postJson('/api/departments', [
            'name' => 'Existing Dept',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    });

    test('update modifies department', function () {
        $department = Department::factory()->create();
        $data = [
            'name' => 'Updated Dept',
        ];

        $response = putJson("/api/departments/{$department->id}", $data);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Dept']);

        assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Updated Dept']);
    });

    test('destroy removes department', function () {
        $department = Department::factory()->create();

        $response = deleteJson("/api/departments/{$department->id}");

        $response->assertNoContent();
        assertDatabaseMissing('departments', ['id' => $department->id]);
    });
});
