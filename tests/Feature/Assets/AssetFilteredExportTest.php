<?php

namespace Tests\Feature\Assets;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Branch;
use App\Models\User;
use App\Models\Employee;
use App\Models\Permission;
use App\Exports\AssetExport;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

test('can export assets with category filter', function () {
    Excel::fake();
    
    $category1 = AssetCategory::factory()->create();
    $category2 = AssetCategory::factory()->create();
    
    Asset::factory()->count(3)->create(['asset_category_id' => $category1->id]);
    Asset::factory()->count(2)->create(['asset_category_id' => $category2->id]);

    $response = $this->postJson('/api/assets/export', [
        'asset_category_id' => $category1->id
    ]);

    $response->assertStatus(200);
    $filename = $response->json('filename');
    
    Excel::assertStored('exports/' . $filename, 'public', function (AssetExport $export) use ($category1) {
        $query = $export->query();
        $results = $query->get();
        
        return $results->count() === 3 && 
               $results->every(fn($asset) => $asset->asset_category_id === $category1->id);
    });
});

test('can export assets with search query', function () {
    Excel::fake();
    
    Asset::factory()->create(['name' => 'Target Asset']);
    Asset::factory()->create(['name' => 'Other Item']);

    $response = $this->postJson('/api/assets/export', [
        'search' => 'Target'
    ]);

    $response->assertStatus(200);
    $filename = $response->json('filename');
    
    Excel::assertStored('exports/' . $filename, 'public', function (AssetExport $export) {
        $query = $export->query();
        $results = $query->get();
        
        return $results->count() === 1 && 
               $results->first()->name === 'Target Asset';
    });
});

test('can export assets with branch filter', function () {
    Excel::fake();
    
    $branch1 = Branch::factory()->create();
    $branch2 = Branch::factory()->create();
    
    Asset::factory()->count(3)->create(['branch_id' => $branch1->id]);
    Asset::factory()->count(2)->create(['branch_id' => $branch2->id]);

    $response = $this->postJson('/api/assets/export', [
        'branch_id' => $branch1->id
    ]);

    $response->assertStatus(200);
    $filename = $response->json('filename');
    
    Excel::assertStored('exports/' . $filename, 'public', function (AssetExport $export) use ($branch1) {
        $query = $export->query();
        $results = $query->get();
        
        return $results->count() === 3 && 
               $results->every(fn($asset) => $asset->branch_id === $branch1->id);
    });
});

test('can export assets with condition filter', function () {
    Excel::fake();
    
    Asset::factory()->count(3)->create(['condition' => 'good']);
    Asset::factory()->count(2)->create(['condition' => 'damaged']);

    $response = $this->postJson('/api/assets/export', [
        'condition' => 'good'
    ]);

    $response->assertStatus(200);
    $filename = $response->json('filename');
    
    Excel::assertStored('exports/' . $filename, 'public', function (AssetExport $export) {
        $query = $export->query();
        $results = $query->get();
        
        return $results->count() === 3 && 
               $results->every(fn($asset) => $asset->condition === 'good');
    });
});
