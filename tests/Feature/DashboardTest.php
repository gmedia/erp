<?php

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class)->group('dashboard');

describe('Dashboard', function () {
    test('authenticated user can see totals on dashboard', function () {
        $user = User::factory()->create();
        actingAs($user);

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

        $response = get('/dashboard');

        $response->assertOk()->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('totals.customers', 2)
            ->where('totals.employees', 3)
            ->where('totals.suppliers', 4)
            ->where('totals.assets', 5)
        );
    });
});

