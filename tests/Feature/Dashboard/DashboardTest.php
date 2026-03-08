<?php

namespace Tests\Feature\Dashboard;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('dashboard');

describe('Dashboard', function () {
    test('authenticated user can see totals on dashboard', function () {
        $user = User::factory()->create();
        \Laravel\Sanctum\Sanctum::actingAs($user, ['*']);

        Customer::factory()->count(2)->create();
        Employee::factory()->count(3)->create();
        Supplier::factory()->count(4)->create();
        Asset::factory()
            ->count(5)
            ->state([
                'employee_id' => null,
                'supplier_id' => null,
            ])
            ->create();

        $response = getJson('/api/dashboard');

        $response->assertOk()->assertJsonFragment([
            'customers' => 2,
            'employees' => 3,
            'suppliers' => 4,
            'assets' => 5
        ]);
    });
});
