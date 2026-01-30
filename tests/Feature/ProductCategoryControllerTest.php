<?php

namespace Tests\Feature;

use App\Models\ProductCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class ProductCategoryControllerTest extends TestCase
{
    use RefreshDatabase, SimpleCrudTestTrait;

    protected $modelClass = ProductCategory::class;
    protected $endpoint = '/api/product-categories';
    protected $permissionPrefix = 'product_category';
    protected $structure = ['id', 'name', 'description', 'created_at', 'updated_at'];

    public function test_it_creates_resource_with_description_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $data = [
            'name' => 'New Category',
            'description' => 'A detailed description for the category'
        ];
        
        $response = $this->postJson($this->endpoint, $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'New Category')
            ->assertJsonPath('data.description', 'A detailed description for the category');

        $this->assertDatabaseHas($this->modelClass, $data);
    }

    public function test_it_updates_resource_with_description_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $resource = $this->modelClass::factory()->create([
            'name' => 'Old Name',
            'description' => 'Old Description'
        ]);

        $data = [
            'name' => 'Updated Name',
            'description' => 'Updated Description'
        ];

        $response = $this->putJson("{$this->endpoint}/{$resource->id}", $data);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.description', 'Updated Description');

        $this->assertDatabaseHas($this->modelClass, ['id' => $resource->id] + $data);
    }
}
