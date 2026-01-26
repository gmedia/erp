<?php

namespace Tests\Feature;

use App\Models\CustomerCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CustomerCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_index_displays_categories_list(): void
    {
        CustomerCategory::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('customer-categories.index'));

        $response->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('customer-categories/index')
                ->has('categories.data', 3)
                ->has('filters')
            );
    }

    public function test_store_creates_new_category(): void
    {
        $data = ['name' => 'New Category'];

        $response = $this->actingAs($this->user)->post(route('customer-categories.store'), $data);

        $response->assertStatus(201);
        $this->assertDatabaseHas('customer_categories', $data);
    }

    public function test_update_modifies_existing_category(): void
    {
        $category = CustomerCategory::factory()->create();
        $data = ['name' => 'Updated Name'];

        $response = $this->actingAs($this->user)->put(route('customer-categories.update', $category), $data);

        $response->assertStatus(200);
        $this->assertDatabaseHas('customer_categories', ['id' => $category->id, 'name' => 'Updated Name']);
    }

    public function test_destroy_deletes_category(): void
    {
        $category = CustomerCategory::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('customer-categories.destroy', $category));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('customer_categories', ['id' => $category->id]);
    }
}
