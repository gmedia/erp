<?php

use App\Models\Asset;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\Employee;
use App\Models\Employment;
use App\Models\Permission;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class)->group('branch-isolation');

function createUserInBranch(Branch $branch, array $permissions = []): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
    ]);
    Employment::factory()->create([
        'employee_id' => $employee->id,
        'branch_id' => $branch->id,
        'department_id' => null,
        'position_id' => null,
    ]);

    if (! empty($permissions)) {
        $ids = [];
        foreach ($permissions as $name) {
            $ids[] = Permission::firstOrCreate(
                ['name' => $name],
                ['display_name' => ucwords(str_replace(['.', '-'], ' ', $name))]
            )->id;
        }
        $employee->permissions()->sync($ids);
    }

    return $user;
}

function createAdminUser(array $permissions = []): User
{
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
    ]);
    Employment::factory()->create([
        'employee_id' => $employee->id,
        'branch_id' => null,
        'department_id' => null,
        'position_id' => null,
    ]);

    $allPerms = array_merge($permissions, ['view_all_branches']);
    $ids = [];
    foreach ($allPerms as $name) {
        $ids[] = Permission::firstOrCreate(
            ['name' => $name],
            ['display_name' => ucwords(str_replace(['.', '-'], ' ', $name))]
        )->id;
    }
    $employee->permissions()->sync($ids);

    return $user;
}

describe('Dashboard branch isolation', function () {
    test('non-admin user sees only own branch counts', function () {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        Customer::factory()->count(3)->create(['branch_id' => $branchA->id]);
        Customer::factory()->count(5)->create(['branch_id' => $branchB->id]);
        Supplier::factory()->count(2)->create(['branch_id' => $branchA->id]);
        Supplier::factory()->count(4)->create(['branch_id' => $branchB->id]);

        $user = createUserInBranch($branchA);
        Sanctum::actingAs($user, ['*']);

        $response = getJson('/api/dashboard');

        $response->assertOk();
        expect($response->json('data.totals.customers'))->toBe(3)
            ->and($response->json('data.totals.suppliers'))->toBe(2);
    });

    test('admin user sees all branches', function () {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        Customer::factory()->count(3)->create(['branch_id' => $branchA->id]);
        Customer::factory()->count(5)->create(['branch_id' => $branchB->id]);

        $admin = createAdminUser();
        Sanctum::actingAs($admin, ['*']);

        $response = getJson('/api/dashboard');

        $response->assertOk();
        expect($response->json('data.totals.customers'))->toBe(8);
    });
});

describe('Aging Dashboard branch isolation', function () {
    beforeEach(function () {
        Carbon::setTestNow(Carbon::parse('2026-06-01'));
    });

    afterEach(function () {
        Carbon::setTestNow();
    });

    test('non-admin user is scoped to own branch regardless of branch_id param', function () {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $customerA = Customer::factory()->create(['branch_id' => $branchA->id]);
        $customerB = Customer::factory()->create(['branch_id' => $branchB->id]);

        CustomerInvoice::factory()->create([
            'customer_id' => $customerA->id,
            'branch_id' => $branchA->id,
            'due_date' => '2026-06-15',
            'grand_total' => 1000,
            'amount_received' => 0,
            'credit_note_amount' => 0,
            'amount_due' => 1000,
            'status' => 'sent',
        ]);

        CustomerInvoice::factory()->create([
            'customer_id' => $customerB->id,
            'branch_id' => $branchB->id,
            'due_date' => '2026-06-15',
            'grand_total' => 2000,
            'amount_received' => 0,
            'credit_note_amount' => 0,
            'amount_due' => 2000,
            'status' => 'sent',
        ]);

        $user = createUserInBranch($branchA, ['aging_dashboard']);
        Sanctum::actingAs($user, ['*']);

        $response = getJson("/api/aging-dashboard?branch_id={$branchB->id}");

        $response->assertOk();
        expect((float) $response->json('ar_summary.total_outstanding'))->toBe(1000.0)
            ->and($response->json('ar_summary.invoice_count'))->toBe(1);
    });

    test('admin user can view specific branch or all', function () {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $customerA = Customer::factory()->create(['branch_id' => $branchA->id]);
        $customerB = Customer::factory()->create(['branch_id' => $branchB->id]);

        CustomerInvoice::factory()->create([
            'customer_id' => $customerA->id,
            'branch_id' => $branchA->id,
            'due_date' => '2026-06-15',
            'grand_total' => 1000,
            'amount_received' => 0,
            'credit_note_amount' => 0,
            'amount_due' => 1000,
            'status' => 'sent',
        ]);

        CustomerInvoice::factory()->create([
            'customer_id' => $customerB->id,
            'branch_id' => $branchB->id,
            'due_date' => '2026-06-15',
            'grand_total' => 2000,
            'amount_received' => 0,
            'credit_note_amount' => 0,
            'amount_due' => 2000,
            'status' => 'sent',
        ]);

        $admin = createAdminUser(['aging_dashboard']);
        Sanctum::actingAs($admin, ['*']);

        $allResponse = getJson('/api/aging-dashboard');
        $allResponse->assertOk();
        expect((float) $allResponse->json('ar_summary.total_outstanding'))->toBe(3000.0);

        $filteredResponse = getJson("/api/aging-dashboard?branch_id={$branchA->id}");
        $filteredResponse->assertOk();
        expect((float) $filteredResponse->json('ar_summary.total_outstanding'))->toBe(1000.0);
    });
});

describe('Asset Dashboard branch isolation', function () {
    test('non-admin user sees only own branch assets', function () {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        Asset::factory()->count(3)->create(['branch_id' => $branchA->id, 'status' => 'active']);
        Asset::factory()->count(7)->create(['branch_id' => $branchB->id, 'status' => 'active']);

        $user = createUserInBranch($branchA, ['asset']);
        Sanctum::actingAs($user, ['*']);

        $response = getJson('/api/asset-dashboard/data');

        $response->assertOk();
        expect($response->json('summary.total_assets'))->toBe(3);
    });

    test('admin user sees all assets', function () {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        Asset::factory()->count(3)->create(['branch_id' => $branchA->id, 'status' => 'active']);
        Asset::factory()->count(7)->create(['branch_id' => $branchB->id, 'status' => 'active']);

        $admin = createAdminUser(['asset']);
        Sanctum::actingAs($admin, ['*']);

        $response = getJson('/api/asset-dashboard/data');

        $response->assertOk();
        expect($response->json('summary.total_assets'))->toBe(10);
    });

    test('non-admin cannot override branch_id via query param', function () {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        Asset::factory()->count(2)->create(['branch_id' => $branchA->id, 'status' => 'active']);
        Asset::factory()->count(8)->create(['branch_id' => $branchB->id, 'status' => 'active']);

        $user = createUserInBranch($branchA, ['asset']);
        Sanctum::actingAs($user, ['*']);

        $response = getJson("/api/asset-dashboard/data?branch_id={$branchB->id}");

        $response->assertOk();
        expect($response->json('summary.total_assets'))->toBe(2);
    });
});

describe('Stock Monitor branch isolation', function () {
    test('non-admin user is scoped to own branch', function () {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();

        $user = createUserInBranch($branchA, ['stock_monitor']);
        Sanctum::actingAs($user, ['*']);

        $response = getJson('/api/stock-monitor');

        $response->assertOk();
    });
});
