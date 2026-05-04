<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\GoodsReceipt;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\User;
use Illuminate\Database\Seeder;

class ApSampleDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id') ?? User::query()->value('id');
        $branchId = Branch::query()->value('id');
        $fiscalYear = FiscalYear::query()->where('status', 'open')->first();

        if (! $adminUserId || ! $branchId || ! $fiscalYear) {
            return;
        }

        $suppliers = Supplier::query()->take(3)->get();
        if ($suppliers->isEmpty()) {
            return;
        }

        $products = Product::query()->orderBy('id')->take(6)->get();
        if ($products->isEmpty()) {
            return;
        }

        $expenseAccounts = Account::query()
            ->where('type', 'expense')
            ->where('is_active', true)
            ->take(3)
            ->get();

        $assetAccounts = Account::query()
            ->where('type', 'asset')
            ->where('is_active', true)
            ->take(2)
            ->get();

        $purchaseOrders = PurchaseOrder::query()->take(2)->get();
        $goodsReceipts = GoodsReceipt::query()->take(2)->get();

        $supplierBills = $this->seedSupplierBills(
            $adminUserId,
            $branchId,
            $fiscalYear->id,
            $suppliers,
            $products,
            $expenseAccounts,
            $assetAccounts,
            $purchaseOrders,
            $goodsReceipts
        );

        $this->seedApPayments(
            $adminUserId,
            $branchId,
            $fiscalYear->id,
            $suppliers,
            $supplierBills
        );
    }

    private function seedSupplierBills(
        int $adminUserId,
        int $branchId,
        int $fiscalYearId,
        $suppliers,
        $products,
        $expenseAccounts,
        $assetAccounts,
        $purchaseOrders,
        $goodsReceipts
    ): array {
        $year = now()->format('Y');

        $billDefinitions = [
            [
                'number' => "BILL-{$year}-000001",
                'status' => 'draft',
                'supplier_index' => 0,
                'total_amount' => 15000000,
                'item_count' => 3,
                'due_date_offset' => 30,
                'confirmed' => false,
            ],
            [
                'number' => "BILL-{$year}-000002",
                'status' => 'confirmed',
                'supplier_index' => 1,
                'total_amount' => 25000000,
                'item_count' => 4,
                'due_date_offset' => 45,
                'confirmed' => true,
            ],
            [
                'number' => "BILL-{$year}-000003",
                'status' => 'confirmed',
                'supplier_index' => 0,
                'total_amount' => 30000000,
                'item_count' => 3,
                'due_date_offset' => 60,
                'confirmed' => true,
            ],
            [
                'number' => "BILL-{$year}-000004",
                'status' => 'confirmed',
                'supplier_index' => 2,
                'total_amount' => 20000000,
                'item_count' => 2,
                'due_date_offset' => 30,
                'confirmed' => true,
            ],
            [
                'number' => "BILL-{$year}-000005",
                'status' => 'overdue',
                'supplier_index' => 1,
                'total_amount' => 18000000,
                'item_count' => 2,
                'due_date_offset' => -15,
                'confirmed' => true,
            ],
            [
                'number' => "BILL-{$year}-000006",
                'status' => 'cancelled',
                'supplier_index' => 0,
                'total_amount' => 12000000,
                'item_count' => 2,
                'due_date_offset' => 30,
                'confirmed' => false,
            ],
            [
                'number' => "BILL-{$year}-000007",
                'status' => 'void',
                'supplier_index' => 2,
                'total_amount' => 22000000,
                'item_count' => 3,
                'due_date_offset' => 30,
                'confirmed' => true,
            ],
        ];

        $supplierBills = [];

        foreach ($billDefinitions as $index => $definition) {
            $supplier = $suppliers[$definition['supplier_index'] % $suppliers->count()];
            $purchaseOrder = $purchaseOrders[$index % $purchaseOrders->count()] ?? null;
            $goodsReceipt = $goodsReceipts[$index % $goodsReceipts->count()] ?? null;

            $billDate = now()->subDays(60 - $index);
            $dueDate = $billDate->copy()->addDays($definition['due_date_offset']);

            $subtotal = $definition['total_amount'] * 0.85;
            $taxAmount = $definition['total_amount'] * 0.11;
            $discountAmount = $definition['total_amount'] * 0.04;
            $grandTotal = $subtotal + $taxAmount - $discountAmount;

            $supplierBill = SupplierBill::updateOrCreate(
                ['bill_number' => $definition['number']],
                [
                    'supplier_id' => $supplier->id,
                    'branch_id' => $branchId,
                    'fiscal_year_id' => $fiscalYearId,
                    'purchase_order_id' => $purchaseOrder?->id,
                    'goods_receipt_id' => $goodsReceipt?->id,
                    'supplier_invoice_number' => 'INV-' . now()->format('Ymd') . '-' . ($index + 1000),
                    'supplier_invoice_date' => $billDate->toDateString(),
                    'bill_date' => $billDate->toDateString(),
                    'due_date' => $dueDate->toDateString(),
                    'payment_terms' => 'Net ' . abs($definition['due_date_offset']),
                    'currency' => 'IDR',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'grand_total' => $grandTotal,
                    'amount_paid' => 0,
                    'amount_due' => $grandTotal,
                    'status' => $definition['status'],
                    'notes' => 'Sample supplier bill for testing Accounts Payable module.',
                    'created_by' => $adminUserId,
                    'confirmed_by' => $definition['confirmed'] ? $adminUserId : null,
                    'confirmed_at' => $definition['confirmed'] ? $billDate->copy()->addHours(2) : null,
                ]
            );

            $this->seedSupplierBillItems(
                $supplierBill,
                $products,
                $expenseAccounts,
                $assetAccounts,
                $definition['item_count'],
                $index
            );

            $supplierBills[] = $supplierBill;
        }

        return $supplierBills;
    }

    private function seedSupplierBillItems(
        SupplierBill $supplierBill,
        $products,
        $expenseAccounts,
        $assetAccounts,
        int $itemCount,
        int $billIndex
    ): void {
        for ($i = 0; $i < $itemCount; $i++) {
            $product = $products[($billIndex + $i) % $products->count()];
            $account = $i % 2 === 0
                ? $expenseAccounts[$i % $expenseAccounts->count()]
                : $assetAccounts[$i % $assetAccounts->count()];

            $quantity = 10 + ($i * 5);
            $unitPrice = 100000 + ($i * 25000);
            $discountPercent = $i === 0 ? 5.0 : 0.0;
            $taxPercent = 11.0;
            $lineTotal = $quantity * $unitPrice * (1 - $discountPercent / 100) * (1 + $taxPercent / 100);

            SupplierBillItem::updateOrCreate(
                [
                    'supplier_bill_id' => $supplierBill->id,
                    'product_id' => $product->id,
                ],
                [
                    'account_id' => $account->id,
                    'description' => $product->name . ' - Item ' . ($i + 1),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_percent' => $discountPercent,
                    'tax_percent' => $taxPercent,
                    'line_total' => $lineTotal,
                    'notes' => 'Sample bill item for testing.',
                ]
            );
        }
    }

    private function seedApPayments(
        int $adminUserId,
        int $branchId,
        int $fiscalYearId,
        $suppliers,
        array $supplierBills
    ): void {
        $year = now()->format('Y');

        $paymentDefinitions = [
            [
                'number' => "PAY-{$year}-000001",
                'status' => 'draft',
                'supplier_index' => 0,
                'total_amount' => 10000000,
                'allocations' => [
                    ['bill_index' => 2, 'amount' => 10000000],
                ],
            ],
            [
                'number' => "PAY-{$year}-000002",
                'status' => 'confirmed',
                'supplier_index' => 2,
                'total_amount' => 20000000,
                'allocations' => [
                    ['bill_index' => 3, 'amount' => 20000000],
                ],
            ],
            [
                'number' => "PAY-{$year}-000003",
                'status' => 'confirmed',
                'supplier_index' => 1,
                'total_amount' => 15000000,
                'allocations' => [
                    ['bill_index' => 2, 'amount' => 5000000],
                    ['bill_index' => 1, 'amount' => 10000000],
                ],
            ],
        ];

        $bankAccount = Account::query()
            ->where('type', 'asset')
            ->where('code', 'like', '111%')
            ->first();

        $billAllocations = [];

        foreach ($paymentDefinitions as $index => $definition) {
            $supplier = $suppliers[$definition['supplier_index'] % $suppliers->count()];
            $paymentDate = now()->subDays(30 - $index);

            $apPayment = ApPayment::updateOrCreate(
                ['payment_number' => $definition['number']],
                [
                    'supplier_id' => $supplier->id,
                    'branch_id' => $branchId,
                    'fiscal_year_id' => $fiscalYearId,
                    'payment_date' => $paymentDate->toDateString(),
                    'payment_method' => 'bank_transfer',
                    'bank_account_id' => $bankAccount?->id,
                    'currency' => 'IDR',
                    'total_amount' => $definition['total_amount'],
                    'total_allocated' => $definition['total_amount'],
                    'total_unallocated' => 0,
                    'reference' => 'REF-' . now()->format('Ymd') . '-' . ($index + 100),
                    'status' => $definition['status'],
                    'notes' => 'Sample AP payment for testing.',
                    'created_by' => $adminUserId,
                    'confirmed_by' => $definition['status'] === 'confirmed' ? $adminUserId : null,
                    'confirmed_at' => $definition['status'] === 'confirmed' ? $paymentDate->copy()->addHours(1) : null,
                ]
            );

            $totalAllocated = 0;
            foreach ($definition['allocations'] as $allocation) {
                $bill = $supplierBills[$allocation['bill_index']] ?? null;
                if (! $bill) {
                    continue;
                }

                ApPaymentAllocation::updateOrCreate(
                    [
                        'ap_payment_id' => $apPayment->id,
                        'supplier_bill_id' => $bill->id,
                    ],
                    [
                        'allocated_amount' => $allocation['amount'],
                        'discount_taken' => 0,
                        'notes' => 'Payment allocation for testing.',
                    ]
                );

                $totalAllocated += $allocation['amount'];

                if (! isset($billAllocations[$bill->id])) {
                    $billAllocations[$bill->id] = 0;
                }
                $billAllocations[$bill->id] += $allocation['amount'];
            }

            if ($totalAllocated > 0) {
                $apPayment->total_allocated = $totalAllocated;
                $apPayment->total_unallocated = $apPayment->total_amount - $totalAllocated;
                $apPayment->save();
            }
        }

        foreach ($billAllocations as $billId => $allocatedAmount) {
            $bill = SupplierBill::find($billId);
            if ($bill) {
                $bill->amount_paid = $allocatedAmount;
                $bill->amount_due = $bill->grand_total - $allocatedAmount;
                $bill->updatePaymentStatus();
                $bill->save();
            }
        }
    }
}
