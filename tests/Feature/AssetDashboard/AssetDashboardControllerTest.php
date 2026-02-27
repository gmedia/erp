<?php

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetMaintenance;
use App\Models\Permission;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Traits\CreatesTestUserWithPermissions;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class, CreatesTestUserWithPermissions::class)->group('asset-dashboard');

describe('Asset Dashboard View', function () {
    test('unauthenticated user cannot access asset dashboard', function () {
        $response = get('/asset-dashboard');
        $response->assertRedirect('/login');
    });

    test('authenticated user without permission cannot access asset dashboard', function () {
        $user = $this->createTestUserWithPermissions(); // Helper creates user with given permission (or none if empty)
        actingAs($user);

        $response = get('/asset-dashboard');
        $response->assertForbidden();
    });

    test('authenticated user with permission can access asset dashboard page', function () {
        $user = $this->createTestUserWithPermissions(['asset']);
        actingAs($user);

        $response = get('/asset-dashboard');
        
        $response->assertOk()
            ->assertInertia(fn ($page) => $page->component('asset-dashboard/index'));
    });
});

describe('Asset Dashboard Data API', function () {
    beforeEach(function () {
        actingAs($this->createTestUserWithPermissions(['asset']));
    });

    test('returns correct JSON structure', function () {
        $response = get('/api/asset-dashboard/data');

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_assets',
                    'total_purchase_cost',
                    'total_book_value',
                    'total_accumulated_depreciation',
                ],
                'status_distribution',
                'category_distribution',
                'condition_overview',
                'recent_maintenances',
                'warranty_alerts',
            ]);
    });

    test('calculates summary totals correctly', function () {
        // Create some assets
        Asset::factory()->create([
            'purchase_cost' => 1000000,
            'book_value' => 800000,
            'accumulated_depreciation' => 200000,
        ]);
        
        Asset::factory()->create([
            'purchase_cost' => 2500000,
            'book_value' => 2000000,
            'accumulated_depreciation' => 500000,
        ]);

        $response = get('/api/asset-dashboard/data');

        $response->assertOk()
            ->assertJsonPath('summary.total_assets', 2)
            ->assertJsonPath('summary.total_purchase_cost', 3500000)
            ->assertJsonPath('summary.total_book_value', 2800000)
            ->assertJsonPath('summary.total_accumulated_depreciation', 700000);
    });

    test('groups status distribution correctly', function () {
        Asset::factory()->count(3)->create(['status' => 'active']);
        Asset::factory()->count(2)->create(['status' => 'maintenance']);
        Asset::factory()->count(1)->create(['status' => 'draft']);

        $response = get('/api/asset-dashboard/data');
        $statusDistribution = collect($response->json('status_distribution'));

        expect($statusDistribution->firstWhere('id', 'active')['count'])->toBe(3)
            ->and($statusDistribution->firstWhere('id', 'maintenance')['count'])->toBe(2)
            ->and($statusDistribution->firstWhere('id', 'draft')['count'])->toBe(1)
            ->and($statusDistribution->firstWhere('id', 'disposed')['count'])->toBe(0); // Check zero count case
    });

    test('groups category distribution correctly', function () {
        $cat1 = AssetCategory::factory()->create(['name' => 'Laptops']);
        $cat2 = AssetCategory::factory()->create(['name' => 'Vehicles']);

        Asset::factory()->count(4)->create(['asset_category_id' => $cat1->id]);
        Asset::factory()->count(2)->create(['asset_category_id' => $cat2->id]);

        $response = get('/api/asset-dashboard/data');
        $categoryDistribution = collect($response->json('category_distribution'));

        expect($categoryDistribution->firstWhere('name', 'Laptops')['count'])->toBe(4)
            ->and($categoryDistribution->firstWhere('name', 'Vehicles')['count'])->toBe(2);
    });

    test('returns recent maintenances', function () {
        $asset = Asset::factory()->create();
        
        // Old maintenance (completed)
        AssetMaintenance::factory()->create([
            'asset_id' => $asset->id,
            'status' => 'completed',
            'scheduled_at' => Carbon::now()->subDays(10),
        ]);

        // Scheduled maintenance
        $futureMaintenance = AssetMaintenance::factory()->create([
            'asset_id' => $asset->id,
            'status' => 'scheduled',
            'scheduled_at' => Carbon::now()->addDays(5),
            'maintenance_type' => 'preventive',
        ]);

        $response = get('/api/asset-dashboard/data');
        
        $response->assertOk()
            ->assertJsonCount(1, 'recent_maintenances')
            ->assertJsonPath('recent_maintenances.0.id', $futureMaintenance->id)
            ->assertJsonPath('recent_maintenances.0.status', 'scheduled');
    });

    test('returns warranty alerts for assets expiring soon', function () {
        // Expired (should not appear)
        Asset::factory()->create([
            'status' => 'active',
            'warranty_end_date' => Carbon::now()->subDays(5)->toDateString(),
        ]);
        
        // Valid but far away (should not appear)
        Asset::factory()->create([
            'status' => 'active',
            'warranty_end_date' => Carbon::now()->addDays(60)->toDateString(),
        ]);

        // Exact target (next 10 days)
        $targetAsset = Asset::factory()->create([
            'status' => 'active',
            'warranty_end_date' => Carbon::now()->addDays(10)->toDateString(),
        ]);

        $response = get('/api/asset-dashboard/data');
        
        $response->assertOk()
            ->assertJsonCount(1, 'warranty_alerts')
            ->assertJsonPath('warranty_alerts.0.id', $targetAsset->id)
            ->assertJsonPath('warranty_alerts.0.days_remaining', 10);
    });
});
