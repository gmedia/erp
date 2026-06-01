<?php

use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\Supplier;
use App\Models\SupplierBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\Traits\CreatesTestUserWithPermissions;

use function Pest\Laravel\getJson;

uses(RefreshDatabase::class, CreatesTestUserWithPermissions::class)->group('aging-dashboard');

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-06-01'));
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('Aging Dashboard API', function () {
    test('returns full JSON structure with five buckets in expected order', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $response = getJson('/api/aging-dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'as_of_date',
                'branches',
                'selected_branch_id',
                'ar_summary' => [
                    'total_outstanding',
                    'current',
                    '1_30',
                    '31_60',
                    '61_90',
                    'over_90',
                    'overdue_amount',
                    'overdue_percentage',
                    'invoice_count',
                    'overdue_count',
                ],
                'ap_summary' => [
                    'total_outstanding',
                    'current',
                    '1_30',
                    '31_60',
                    '61_90',
                    'over_90',
                    'overdue_amount',
                    'overdue_percentage',
                    'invoice_count',
                    'overdue_count',
                ],
                'ar_buckets' => [['label', 'amount', 'percentage']],
                'ap_buckets' => [['label', 'amount', 'percentage']],
                'top_overdue_customers',
                'top_overdue_suppliers',
            ]);

        $arBuckets = $response->json('ar_buckets');
        expect($arBuckets)->toHaveCount(5)
            ->and(array_column($arBuckets, 'label'))->toBe([
                'Current',
                '1-30 Days',
                '31-60 Days',
                '61-90 Days',
                'Over 90 Days',
            ]);

        $apBuckets = $response->json('ap_buckets');
        expect($apBuckets)->toHaveCount(5)
            ->and(array_column($apBuckets, 'label'))->toBe([
                'Current',
                '1-30 Days',
                '31-60 Days',
                '61-90 Days',
                'Over 90 Days',
            ]);
    });

    test('returns zero state when no invoices or bills exist', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $response = getJson('/api/aging-dashboard');

        $response->assertOk()
            ->assertJsonPath('ar_summary.invoice_count', 0)
            ->assertJsonPath('ar_summary.overdue_count', 0)
            ->assertJsonPath('ap_summary.invoice_count', 0)
            ->assertJsonPath('top_overdue_customers', [])
            ->assertJsonPath('top_overdue_suppliers', []);

        expect((float) $response->json('ar_summary.total_outstanding'))->toBe(0.0)
            ->and((float) $response->json('ar_summary.overdue_amount'))->toBe(0.0)
            ->and((float) $response->json('ar_summary.overdue_percentage'))->toBe(0.0)
            ->and((float) $response->json('ap_summary.total_outstanding'))->toBe(0.0);

        foreach ($response->json('ar_buckets') as $bucket) {
            expect((float) $bucket['amount'])->toBe(0.0)
                ->and((float) $bucket['percentage'])->toBe(0.0);
        }
    });

    test('classifies invoices into correct aging buckets', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create(['branch_id' => $branch->id]);

        $cases = [
            ['due_date' => '2026-06-15', 'expected_bucket' => 'current'],
            ['due_date' => '2026-05-17', 'expected_bucket' => '1_30'],
            ['due_date' => '2026-04-17', 'expected_bucket' => '31_60'],
            ['due_date' => '2026-03-18', 'expected_bucket' => '61_90'],
            ['due_date' => '2026-02-01', 'expected_bucket' => 'over_90'],
        ];

        foreach ($cases as $case) {
            CustomerInvoice::factory()->create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'due_date' => $case['due_date'],
                'grand_total' => 1000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'amount_due' => 1000,
                'status' => 'sent',
            ]);
        }

        $response = getJson('/api/aging-dashboard');

        $response->assertOk()
            ->assertJsonPath('ar_summary.invoice_count', 5)
            ->assertJsonPath('ar_summary.overdue_count', 4);

        $arSummary = $response->json('ar_summary');
        expect((float) $arSummary['total_outstanding'])->toBe(5000.0)
            ->and((float) $arSummary['current'])->toBe(1000.0)
            ->and((float) $arSummary['1_30'])->toBe(1000.0)
            ->and((float) $arSummary['31_60'])->toBe(1000.0)
            ->and((float) $arSummary['61_90'])->toBe(1000.0)
            ->and((float) $arSummary['over_90'])->toBe(1000.0);
    });

    test('branch_id filter excludes invoices from other branches', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $customerA = Customer::factory()->create(['branch_id' => $branchA->id]);
        $customerB = Customer::factory()->create(['branch_id' => $branchB->id]);

        CustomerInvoice::factory()->create([
            'customer_id' => $customerA->id,
            'branch_id' => $branchA->id,
            'due_date' => '2026-06-30',
            'grand_total' => 5000,
            'amount_received' => 0,
            'credit_note_amount' => 0,
            'amount_due' => 5000,
            'status' => 'sent',
        ]);

        CustomerInvoice::factory()->create([
            'customer_id' => $customerB->id,
            'branch_id' => $branchB->id,
            'due_date' => '2026-06-30',
            'grand_total' => 7000,
            'amount_received' => 0,
            'credit_note_amount' => 0,
            'amount_due' => 7000,
            'status' => 'sent',
        ]);

        $response = getJson("/api/aging-dashboard?branch_id={$branchA->id}");

        $response->assertOk()
            ->assertJsonPath('selected_branch_id', $branchA->id)
            ->assertJsonPath('ar_summary.invoice_count', 1);

        expect((float) $response->json('ar_summary.total_outstanding'))->toBe(5000.0);
    });

    test('status filter excludes draft, paid, and cancelled invoices', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create(['branch_id' => $branch->id]);

        foreach (['draft', 'paid', 'cancelled', 'sent'] as $status) {
            CustomerInvoice::factory()->create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'due_date' => '2026-06-30',
                'grand_total' => 1000,
                'amount_received' => $status === 'paid' ? 1000 : 0,
                'credit_note_amount' => 0,
                'amount_due' => $status === 'paid' ? 0 : 1000,
                'status' => $status,
            ]);
        }

        $response = getJson('/api/aging-dashboard');

        $response->assertOk()
            ->assertJsonPath('ar_summary.invoice_count', 1);

        expect((float) $response->json('ar_summary.total_outstanding'))->toBe(1000.0);
    });

    test('top overdue customers ordered by overdue amount desc and limited to 10', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $branch = Branch::factory()->create();

        $expectedAmounts = [];
        for ($i = 1; $i <= 12; $i++) {
            $customer = Customer::factory()->create(['branch_id' => $branch->id]);
            $amount = $i * 1000;
            $expectedAmounts[] = (float) $amount;

            CustomerInvoice::factory()->create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'due_date' => '2026-04-01',
                'grand_total' => $amount,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'amount_due' => $amount,
                'status' => 'overdue',
            ]);
        }

        $response = getJson('/api/aging-dashboard');

        $response->assertOk();
        $top = $response->json('top_overdue_customers');

        rsort($expectedAmounts);
        $expectedTop = array_slice($expectedAmounts, 0, 10);

        $actual = array_map(fn ($row) => (float) $row['overdue_amount'], $top);

        expect($top)->toHaveCount(10)
            ->and($actual)->toBe($expectedTop);

        $supplierBranch = Branch::factory()->create();
        for ($i = 1; $i <= 12; $i++) {
            $supplier = Supplier::factory()->create();
            $amount = $i * 1500;

            SupplierBill::factory()->create([
                'supplier_id' => $supplier->id,
                'branch_id' => $supplierBranch->id,
                'due_date' => '2026-04-01',
                'subtotal' => $amount,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'grand_total' => $amount,
                'amount_paid' => 0,
                'amount_due' => $amount,
                'status' => 'overdue',
            ]);
        }

        $response2 = getJson("/api/aging-dashboard?branch_id={$supplierBranch->id}");
        $response2->assertOk();
        $topSuppliers = $response2->json('top_overdue_suppliers');

        $expectedSupplierAmounts = [];
        for ($i = 1; $i <= 12; $i++) {
            $expectedSupplierAmounts[] = (float) ($i * 1500);
        }
        rsort($expectedSupplierAmounts);
        $expectedSupplierTop = array_slice($expectedSupplierAmounts, 0, 10);

        $actualSuppliers = array_map(fn ($row) => (float) $row['overdue_amount'], $topSuppliers);

        expect($topSuppliers)->toHaveCount(10)
            ->and($actualSuppliers)->toBe($expectedSupplierTop);
    });

    test('requires authentication', function () {
        app('auth')->forgetGuards();

        $response = getJson('/api/aging-dashboard');

        $response->assertUnauthorized();
    });

    test('requires aging_dashboard permission', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions([]), ['*']);

        $response = getJson('/api/aging-dashboard');

        $response->assertForbidden();
    });

    test('sum of five buckets equals total outstanding', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create(['branch_id' => $branch->id]);

        foreach (['2026-06-15', '2026-05-17', '2026-04-17', '2026-03-18', '2026-02-01'] as $i => $dueDate) {
            CustomerInvoice::factory()->create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'due_date' => $dueDate,
                'grand_total' => 1000 + ($i * 250),
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'amount_due' => 1000 + ($i * 250),
                'status' => 'sent',
            ]);
        }

        $response = getJson('/api/aging-dashboard');

        $summary = $response->assertOk()->json('ar_summary');
        $bucketSum = (float) $summary['current']
            + (float) $summary['1_30']
            + (float) $summary['31_60']
            + (float) $summary['61_90']
            + (float) $summary['over_90'];

        expect(round($bucketSum, 2))->toBe(round((float) $summary['total_outstanding'], 2));
    });

    test('bucket boundary dates are inclusive on both ends with no overlap', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create(['branch_id' => $branch->id]);

        // Carbon::setTestNow is 2026-06-01.
        // Boundaries: today=2026-06-01 → Current: due >= 2026-06-01
        //             1-30: 2026-05-02 to 2026-05-31
        //             31-60: 2026-04-02 to 2026-05-01
        //             61-90: 2026-03-03 to 2026-04-01
        //             >90: < 2026-03-03
        $boundaries = [
            ['due_date' => '2026-06-01', 'bucket' => 'current'],   // exactly today
            ['due_date' => '2026-05-31', 'bucket' => '1_30'],      // today-1
            ['due_date' => '2026-05-02', 'bucket' => '1_30'],      // today-30
            ['due_date' => '2026-05-01', 'bucket' => '31_60'],     // today-31
            ['due_date' => '2026-04-02', 'bucket' => '31_60'],     // today-60
            ['due_date' => '2026-04-01', 'bucket' => '61_90'],     // today-61
            ['due_date' => '2026-03-03', 'bucket' => '61_90'],     // today-90
            ['due_date' => '2026-03-02', 'bucket' => 'over_90'],   // today-91
        ];

        foreach ($boundaries as $case) {
            CustomerInvoice::factory()->create([
                'customer_id' => $customer->id,
                'branch_id' => $branch->id,
                'due_date' => $case['due_date'],
                'grand_total' => 100,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'amount_due' => 100,
                'status' => 'sent',
            ]);
        }

        $response = getJson('/api/aging-dashboard');
        $summary = $response->assertOk()->json('ar_summary');

        // Each invoice = 100 → bucket totals should match counts × 100.
        expect((float) $summary['current'])->toBe(100.0)        // 1 invoice
            ->and((float) $summary['1_30'])->toBe(200.0)        // 2 invoices
            ->and((float) $summary['31_60'])->toBe(200.0)       // 2 invoices
            ->and((float) $summary['61_90'])->toBe(200.0)       // 2 invoices
            ->and((float) $summary['over_90'])->toBe(100.0)     // 1 invoice
            ->and((float) $summary['total_outstanding'])->toBe(800.0);
    });

    test('overdue percentage handles zero outstanding without division error', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $response = getJson('/api/aging-dashboard');

        $response->assertOk()
            ->assertJsonPath('ar_summary.overdue_percentage', 0)
            ->assertJsonPath('ap_summary.overdue_percentage', 0);
    });

    test('respects custom as_of_date parameter for bucketing', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create(['branch_id' => $branch->id]);

        // Invoice due 2026-03-15. With as_of=2026-06-01 (default) it's >78 days = 61_90 bucket.
        // With as_of=2026-04-01 it's only 17 days late = 1_30 bucket.
        CustomerInvoice::factory()->create([
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'due_date' => '2026-03-15',
            'grand_total' => 500,
            'amount_received' => 0,
            'credit_note_amount' => 0,
            'amount_due' => 500,
            'status' => 'sent',
        ]);

        $defaultResponse = getJson('/api/aging-dashboard');
        $defaultSummary = $defaultResponse->assertOk()->json('ar_summary');
        expect((float) $defaultSummary['61_90'])->toBe(500.0)
            ->and((float) $defaultSummary['1_30'])->toBe(0.0);

        $customResponse = getJson('/api/aging-dashboard?as_of_date=2026-04-01');
        $customSummary = $customResponse->assertOk()->json('ar_summary');
        expect((float) $customSummary['1_30'])->toBe(500.0)
            ->and((float) $customSummary['61_90'])->toBe(0.0)
            ->and($customResponse->json('as_of_date'))->toBe('2026-04-01');
    });

    test('invalid as_of_date falls back to today gracefully', function () {
        Sanctum::actingAs($this->createTestUserWithPermissions(['aging_dashboard']), ['*']);

        $response = getJson('/api/aging-dashboard?as_of_date=not-a-real-date');

        $response->assertOk()
            ->assertJsonPath('as_of_date', '2026-06-01');
    });
});
