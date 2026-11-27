<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostApPaymentJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\Department;
use App\Models\GoodsReceipt;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\User;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;

class ApSampleDataSeeder extends Seeder
{
    /** Pola nomor dokumen AP demo yang dibuat seeder ini. */
    private const BILL_DEMO = 'BILL-____-00000%';

    private const PAYMENT_DEMO = 'PAY-____-00000%';

    public function run(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id') ?? User::query()->value('id');
        $branchId = Branch::query()->value('id');
        $fiscalYear = DemoCalendar::currentFiscalYear();

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

        // Hanya akun dari COA version aktif: akun COA archived tidak boleh dipakai posting.
        $activeCoaVersionId = CoaVersion::query()->where('status', 'active')->value('id');

        $expenseAccounts = Account::query()
            ->where('coa_version_id', $activeCoaVersionId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->take(3)
            ->get();

        $assetAccounts = Account::query()
            ->where('coa_version_id', $activeCoaVersionId)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->whereDoesntHave('children')
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

    /**
     * Menyelaraskan amount_paid/amount_due tagihan dengan seluruh alokasi yang masih
     * ada di ledger (demo maupun buatan pengguna). Upsert tagihan mereset amount_paid
     * ke 0, sehingga tanpa ini seed ulang menghapus pembayaran yang sudah dialokasikan.
     */
    private function restoreBillSettlement(SupplierBill $bill): void
    {
        $paid = (float) ApPaymentAllocation::query()
            ->where('supplier_bill_id', $bill->id)
            ->sum('allocated_amount');

        $bill->amount_paid = $paid;
        $bill->amount_due = (float) $bill->grand_total - $paid;
        $bill->save();
        $bill->updatePaymentStatus();
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
        // Departemen dokumen beban disebar bergilir; sebagian sengaja tanpa departemen.
        $expenseDepartmentIds = Department::query()
            ->whereIn('name', [
                DemoMasterData::DEPARTMENT_OPERATIONS,
                DemoMasterData::DEPARTMENT_ENGINEERING,
                DemoMasterData::DEPARTMENT_FINANCE,
            ])
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $billDefinitions = [
            [
                'number' => "BILL-{$year}-000001",
                'bill_date_days_ago' => 56,
                'status' => 'draft',
                'supplier_index' => 0,
                'total_amount' => 15000000,
                'item_count' => 3,
                'due_date_offset' => 30,
                'confirmed' => false,
            ],
            [
                'number' => "BILL-{$year}-000002",
                'bill_date_days_ago' => 55,
                'status' => 'confirmed',
                'supplier_index' => 1,
                'total_amount' => 25000000,
                'item_count' => 4,
                'due_date_offset' => 45,
                'confirmed' => true,
            ],
            [
                'number' => "BILL-{$year}-000003",
                'bill_date_days_ago' => 54,
                'status' => 'confirmed',
                'supplier_index' => 0,
                'total_amount' => 30000000,
                'item_count' => 3,
                'due_date_offset' => 60,
                'confirmed' => true,
            ],
            [
                'number' => "BILL-{$year}-000004",
                'bill_date_days_ago' => 53,
                'status' => 'confirmed',
                'supplier_index' => 2,
                'total_amount' => 20000000,
                'item_count' => 2,
                'due_date_offset' => 30,
                'confirmed' => true,
            ],
            [
                'number' => "BILL-{$year}-000005",
                'bill_date_days_ago' => 75,
                'status' => 'overdue',
                'supplier_index' => 1,
                'total_amount' => 18000000,
                'item_count' => 2,
                'due_date_offset' => 30,
                'confirmed' => true,
            ],
            [
                'number' => "BILL-{$year}-000006",
                'bill_date_days_ago' => 52,
                'status' => 'cancelled',
                'supplier_index' => 0,
                'total_amount' => 12000000,
                'item_count' => 2,
                'due_date_offset' => 30,
                'confirmed' => false,
            ],
            [
                'number' => "BILL-{$year}-000007",
                'bill_date_days_ago' => 51,
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

            // Tanggal dokumen lebih tua untuk bill overdue agar jatuh tempo
            // tetap setelah tanggal dokumen (W5-20).
            $billDate = now()->subDays($definition['bill_date_days_ago']);
            $dueDate = $billDate->copy()->addDays($definition['due_date_offset']);

            // Total dokumen diturunkan dari item (sumber jurnal AP), bukan rasio tetap,
            // agar Σ line_total = grand_total dan GL 21100 = subledger.
            $items = $this->billItemDefinitions($products, $expenseAccounts, $assetAccounts, $definition['item_count'], $index);
            $subtotal = array_sum(array_column($items, 'base'));
            $discountAmount = array_sum(array_column($items, 'discount'));
            $taxAmount = array_sum(array_column($items, 'tax'));
            $grandTotal = array_sum(array_column($items, 'line_total'));

            $supplierBill = SupplierBill::updateOrCreate(
                ['bill_number' => $definition['number']],
                [
                    'supplier_id' => $supplier->id,
                    'branch_id' => $branchId,
                    // Sebagian bill membawa departemen; sisanya sengaja tanpa departemen.
                    'department_id' => $index % 4 === 3 || $expenseDepartmentIds === []
                        ? null
                        : $expenseDepartmentIds[intdiv($index, 2) % count($expenseDepartmentIds)],
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
                    'amount_due' => in_array($definition['status'], ['draft', 'cancelled', 'void'], true)
                        ? 0
                        : $grandTotal,
                    'status' => $definition['status'],
                    'notes' => 'Tagihan supplier atas pembelian barang',
                    'created_by' => $adminUserId,
                    'confirmed_by' => $definition['confirmed'] ? $adminUserId : null,
                    'confirmed_at' => $definition['confirmed'] ? $billDate->copy()->addHours(2) : null,
                ]
            );

            $this->seedSupplierBillItems($supplierBill, $items);
            $this->restoreBillSettlement($supplierBill);

            $supplierBills[] = $supplierBill;
        }

        return $supplierBills;
    }

    /**
     * Definisi item tagihan. Nilai per baris dibulatkan 2 desimal; grand_total bill
     * diturunkan dari Σ line_total sehingga sama dengan jurnal AP.
     *
     * @return list<array<string, mixed>>
     */
    private function billItemDefinitions(
        $products,
        $expenseAccounts,
        $assetAccounts,
        int $itemCount,
        int $billIndex
    ): array {
        $items = [];

        for ($i = 0; $i < $itemCount; $i++) {
            $quantity = 10 + ($i * 5);
            $unitPrice = 100000 + ($i * 25000);
            $discountPercent = $i === 0 ? 5.0 : 0.0;
            $taxPercent = 11.0;

            $base = round($quantity * $unitPrice, 2);
            $discount = round($base * $discountPercent / 100, 2);
            $tax = round(($base - $discount) * $taxPercent / 100, 2);

            $items[] = [
                'product' => $products[($billIndex + $i) % $products->count()],
                'account' => $i % 2 === 0
                    ? $expenseAccounts[$i % $expenseAccounts->count()]
                    : $assetAccounts[$i % $assetAccounts->count()],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => $discountPercent,
                'tax_percent' => $taxPercent,
                'base' => $base,
                'discount' => $discount,
                'tax' => $tax,
                'line_total' => round($base - $discount + $tax, 2),
            ];
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function seedSupplierBillItems(SupplierBill $supplierBill, array $items): void
    {
        foreach ($items as $index => $item) {
            SupplierBillItem::updateOrCreate(
                [
                    'supplier_bill_id' => $supplierBill->id,
                    'product_id' => $item['product']->id,
                ],
                [
                    'account_id' => $item['account']->id,
                    'description' => $item['product']->name . ' - Item ' . ($index + 1),
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount_percent' => $item['discount_percent'],
                    'tax_percent' => $item['tax_percent'],
                    'line_total' => $item['line_total'],
                    'notes' => 'Item tagihan sesuai faktur supplier',
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

        // Alokasi diturunkan dari grand_total bill agar tidak pernah melebihi tagihan.
        $bill4GrandTotal = (float) ($supplierBills[3]->grand_total ?? 0);
        $bill5GrandTotal = (float) ($supplierBills[4]->grand_total ?? 0);
        $bill3Partial = 5000000.0;

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
                'total_amount' => $bill4GrandTotal,
                'allocations' => [
                    ['bill_index' => 3, 'amount' => $bill4GrandTotal],
                ],
            ],
            [
                'number' => "PAY-{$year}-000003",
                'status' => 'confirmed',
                'supplier_index' => 0,
                'total_amount' => $bill3Partial + $bill5GrandTotal,
                'allocations' => [
                    ['bill_index' => 2, 'amount' => $bill3Partial],
                    ['bill_index' => 4, 'amount' => $bill5GrandTotal],
                ],
            ],
        ];

        // Akun bank harus dari COA version aktif agar posting sejalan dengan laporan
        // (W6-06). Transfer bank memakai akun bank (11110), bukan Cash.
        $activeCoaVersionId = CoaVersion::query()->where('status', 'active')->value('id');

        $bankAccount = Account::query()
            ->where('coa_version_id', $activeCoaVersionId)
            ->where('code', '11110')
            ->where('is_active', true)
            ->first();

        $billAllocations = [];

        foreach ($paymentDefinitions as $index => $definition) {
            // Pihak pembayaran = pihak tagihan yang dialokasikan (AP allocation harus satu supplier).
            $allocatedBill = $supplierBills[$definition['allocations'][0]['bill_index']] ?? null;
            $supplier = $allocatedBill !== null
                ? $suppliers->firstWhere('id', $allocatedBill->supplier_id)
                : $suppliers[$definition['supplier_index'] % $suppliers->count()];
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
                    'notes' => 'Pembayaran tagihan supplier melalui transfer bank',
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

                // Satu pembayaran hanya boleh mengalokasikan tagihan milik pihak yang sama.
                if ((int) $bill->supplier_id !== (int) $apPayment->supplier_id) {
                    $bill->update(['supplier_id' => $apPayment->supplier_id]);
                    $bill->refresh();
                }

                // Hanya pembayaran confirmed yang punya jurnal dan boleh tercatat sebagai alokasi.
                // Alokasi dari pembayaran draft tidak masuk ledger, sehingga subledger
                // (ap_payment_allocations) sejalan dengan amount_paid dan GL 21100.
                if ($definition['status'] !== 'confirmed') {
                    ApPaymentAllocation::query()
                        ->where('ap_payment_id', $apPayment->id)
                        ->where('supplier_bill_id', $bill->id)
                        ->delete();

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
                        'notes' => 'Alokasi pembayaran ke tagihan supplier',
                    ]
                );

                $totalAllocated += $allocation['amount'];
                $billAllocations[$bill->id] = ($billAllocations[$bill->id] ?? 0) + $allocation['amount'];
            }

            if ($totalAllocated > 0) {
                $apPayment->total_allocated = $totalAllocated;
                $apPayment->total_unallocated = $apPayment->total_amount - $totalAllocated;
                $apPayment->save();
            }
        }

        foreach (array_keys($billAllocations) as $billId) {
            $bill = SupplierBill::find($billId);
            if ($bill) {
                $this->restoreBillSettlement($bill);
            }
        }

        $this->postConfirmedJournals($fiscalYearId);
    }

    /**
     * Posting jurnal AP lewat action aplikasi (sama dengan controller saat confirm).
     * Idempoten: action mengembalikan jurnal yang sudah ada bila `journal_entry_id` terisi.
     */
    private function postConfirmedJournals(int $fiscalYearId): void
    {
        $postBill = app(PostSupplierBillJournalAction::class);
        $postPayment = app(PostApPaymentJournalAction::class);

        // Jurnal demo AP dibuat ulang setiap run agar nilainya mengikuti dokumen terbaru.
        // Hanya bill/payment demo (nomor dari seeder ini) yang jurnalnya dilepas dan
        // diposting ulang; jurnal dokumen buatan pengguna tidak disentuh (issue #357).
        // Action mengembalikan jurnal lama bila journal_entry_id masih terisi, jadi harus dilepas dulu.
        $billIds = SupplierBill::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('bill_number', 'like', self::BILL_DEMO)
            ->pluck('id');
        $paymentIds = ApPayment::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('payment_number', 'like', self::PAYMENT_DEMO)
            ->pluck('id');

        // Termasuk prefix APP- dari seeder lama yang memposting kas tulis-tangan (sudah dihapus).
        $staleEntryIds = JournalEntry::query()
            ->where(fn ($query) => $query
                ->where(fn ($q) => $q->where('source_type', SupplierBill::class)->whereIn('source_id', $billIds))
                ->orWhere(fn ($q) => $q->where('source_type', ApPayment::class)->whereIn('source_id', $paymentIds))
                ->orWhere(fn ($q) => $q->where('entry_number', 'like', 'APP-%')->where('source_type', ApPayment::class)))
            ->pluck('id');

        if ($staleEntryIds->isNotEmpty()) {
            SupplierBill::query()->whereIn('journal_entry_id', $staleEntryIds)->update(['journal_entry_id' => null]);
            ApPayment::query()->whereIn('journal_entry_id', $staleEntryIds)->update(['journal_entry_id' => null]);
            JournalEntryLine::query()->whereIn('journal_entry_id', $staleEntryIds)->delete();
            JournalEntry::query()->whereIn('id', $staleEntryIds)->delete();
        }

        SupplierBill::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('bill_number', 'like', self::BILL_DEMO)
            ->whereIn('status', PostSupplierBillJournalAction::POSTABLE_STATUSES)
            ->orderBy('bill_number')
            ->get()
            ->each(fn (SupplierBill $bill) => $postBill->execute($bill->refresh()));

        ApPayment::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('payment_number', 'like', self::PAYMENT_DEMO)
            ->where('status', 'confirmed')
            ->orderBy('payment_number')
            ->get()
            ->each(fn (ApPayment $payment) => $postPayment->execute($payment->refresh()));
    }
}
