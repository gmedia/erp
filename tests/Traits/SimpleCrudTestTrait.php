<?php

namespace Tests\Traits;

use App\Models\Employee;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

/**
 * Trait for testing Simple CRUD modules.
 * Requires the consumer to define:
 * - $modelClass: Class string of the model (e.g., Position::class)
 * - $endpoint: Base API endpoint (e.g., '/api/positions')
 * - $permissionPrefix: Prefix for permissions (e.g., 'position')
 * - $structure: Array of field names in JSON response (e.g., ['id', 'name'])
 */
trait SimpleCrudTestTrait
{
    protected function setUpUserWithPermissions(array $permissions = []): User
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);

        if (!empty($permissions)) {
            $permissionIds = [];
            foreach ($permissions as $name) {
                $permissionIds[] = Permission::firstOrCreate(
                    ['name' => $name],
                    ['display_name' => ucwords(str_replace('.', ' ', $name))]
                )->id;
            }
            $employee->permissions()->sync($permissionIds);
        }

        return $user;
    }

    protected function getBasePermissions(): array
    {
        return [
            $this->permissionPrefix,
            "{$this->permissionPrefix}.create",
            "{$this->permissionPrefix}.edit",
            "{$this->permissionPrefix}.delete",
        ];
    }

    public function test_it_returns_paginated_list_with_proper_meta_structure()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $baseline = $this->modelClass::count();

        $this->modelClass::factory()->count(25)->create();

        $response = $this->getJson("{$this->endpoint}?per_page=10");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => $this->structure
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.total', $baseline + 25);
    }

    public function test_it_creates_resource_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $data = ['name' => 'New Resource'];
        
        $response = $this->postJson($this->endpoint, $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Resource');

        $this->assertDatabaseHas($this->modelClass, $data);
    }

    public function test_it_validates_store_request()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $response = $this->postJson($this->endpoint, ['name' => '']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
        
        // Test duplicate name
        $this->modelClass::factory()->create(['name' => 'Existing Name']);
        $response = $this->postJson($this->endpoint, ['name' => 'Existing Name']);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
    }

    public function test_it_shows_resource_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $resource = $this->modelClass::factory()->create();

        $response = $this->getJson("{$this->endpoint}/{$resource->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $resource->id)
            ->assertJsonPath('data.name', $resource->name);
    }

    public function test_it_updates_resource_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $resource = $this->modelClass::factory()->create(['name' => 'Old Name']);

        $response = $this->putJson("{$this->endpoint}/{$resource->id}", ['name' => 'Updated Name']);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas($this->modelClass, ['id' => $resource->id, 'name' => 'Updated Name']);
    }

    public function test_it_validates_update_request()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $resource = $this->modelClass::factory()->create();

        // Test empty name
        $response = $this->putJson("{$this->endpoint}/{$resource->id}", ['name' => '']);
        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);

        // Test duplicate name
        $other = $this->modelClass::factory()->create(['name' => 'Taken Name']);
        $response = $this->putJson("{$this->endpoint}/{$resource->id}", ['name' => 'Taken Name']);
        $response->assertUnprocessable()->assertJsonValidationErrors(['name']);

        // Test updating to self name (should pass)
        $response = $this->putJson("{$this->endpoint}/{$resource->id}", ['name' => $resource->name]);
        $response->assertOk(); // simple-crud-update-request allows optional name or same name
    }

    public function test_it_deletes_resource_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $resource = $this->modelClass::factory()->create();

        $response = $this->deleteJson("{$this->endpoint}/{$resource->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing($this->modelClass, ['id' => $resource->id]);
    }

    public function test_it_exports_data_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $this->modelClass::factory()->count(5)->create();

        $response = $this->postJson("{$this->endpoint}/export");

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
        
        // Verify filename pattern (e.g., positions_export_YYYY-MM-DD_...)
        // We assume the prefix is derived from the table name or endpoint. 
        // But the trait doesn't know the file prefix exactly unless we add a property or infer it.
        // Usually it is snake_case of the model plural or similar. 
        // Let's regex match for `_export_\d{4}` generally.
        $filename = $response->json('filename');
        $this->assertMatchesRegularExpression('/_export_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.xlsx/', $filename);
    }
    
    public function test_it_exports_applies_search_filter()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        // Create data for searching
        $this->modelClass::factory()->create(['name' => 'Alpha Item']);
        $this->modelClass::factory()->create(['name' => 'Beta Item']);

        $response = $this->postJson("{$this->endpoint}/export", ['search' => 'Alpha']);

        $response->assertOk()
            ->assertJsonStructure(['url', 'filename']);
            
        // Note: verifying the actual content of the Excel file is out of scope for unit/feature tests usually, 
        // we trust the ExportAction does its job if it received the filter. 
        // The original test only asserted assertOk and keys.
    }

    public function test_it_enforces_permissions()
    {
        $this->modelClass::query()->delete();
        
        // Case 1: Store without permission
        $user = $this->setUpUserWithPermissions([$this->permissionPrefix]);
        $this->actingAs($user);
        $response = $this->postJson($this->endpoint, ['name' => 'Fail']);
        $response->assertForbidden();

        // Case 2: Update without permission
        $resource = $this->modelClass::factory()->create();
        $response = $this->putJson("{$this->endpoint}/{$resource->id}", ['name' => 'Fail']);
        $response->assertForbidden();

        // Case 3: Delete without permission
        $response = $this->deleteJson("{$this->endpoint}/{$resource->id}");
        $response->assertForbidden();
    }
}
