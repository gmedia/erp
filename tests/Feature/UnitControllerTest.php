<?php

namespace Tests\Feature;

use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\SimpleCrudTestTrait;

class UnitControllerTest extends TestCase
{
    use RefreshDatabase, SimpleCrudTestTrait;

    protected $modelClass = Unit::class;
    protected $endpoint = '/api/units';
    protected $permissionPrefix = 'unit';
    protected $structure = ['id', 'name', 'symbol', 'created_at', 'updated_at'];

    public function test_it_creates_resource_with_symbol_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $data = [
            'name' => 'Kilogram',
            'symbol' => 'kg'
        ];
        
        $response = $this->postJson($this->endpoint, $data);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Kilogram')
            ->assertJsonPath('data.symbol', 'kg');

        $this->assertDatabaseHas($this->modelClass, $data);
    }

    public function test_it_updates_resource_with_symbol_successfully()
    {
        $this->modelClass::query()->delete();
        $user = $this->setUpUserWithPermissions($this->getBasePermissions());
        $this->actingAs($user);

        $resource = $this->modelClass::factory()->create([
            'name' => 'Old Name',
            'symbol' => 'old'
        ]);

        $data = [
            'name' => 'Updated Name',
            'symbol' => 'new'
        ];

        $response = $this->putJson("{$this->endpoint}/{$resource->id}", $data);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.symbol', 'new');

        $this->assertDatabaseHas($this->modelClass, ['id' => $resource->id] + $data);
    }
}
