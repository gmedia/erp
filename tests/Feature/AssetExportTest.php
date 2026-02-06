<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

uses(RefreshDatabase::class)->group('assets', 'asset-feature');

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    
    $this->employee = Employee::factory()->create([
        'user_id' => $this->user->id,
        'email' => $this->user->email,
    ]);

    $pAsset = Permission::firstOrCreate(['name' => 'asset', 'display_name' => 'Asset']);
    $this->employee->permissions()->attach([$pAsset->id]);
});

test('can export assets to excel', function () {
    Excel::fake();
    Asset::factory()->count(5)->create();

    $response = $this->postJson('/api/assets/export');

    $response->assertStatus(200)
        ->assertJsonStructure(['url', 'filename']);
        
    // Excel::fake() automatically asserts that the export was stored if called
});
