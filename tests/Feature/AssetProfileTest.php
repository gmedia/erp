<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\User;
use App\Models\Employee;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class)->group('assets', 'asset-profile');

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

test('asset profile page is accessible and contains data', function () {
    $asset = Asset::factory()->create();

    $response = $this->get(route('assets.profile', ['asset' => $asset->ulid]));

    $response->assertStatus(200);
    $response->assertInertia(fn (Assert $page) => $page
        ->component('assets/profile')
        ->has('asset.data', fn (Assert $data) => $data
            ->where('id', $asset->id)
            ->where('ulid', $asset->ulid)
            ->where('asset_code', $asset->asset_code)
            ->has('movements')
            ->has('maintenances')
            ->has('stocktake_items')
            ->has('depreciation_lines')
            ->etc()
        )
    );
});
