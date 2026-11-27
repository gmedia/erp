<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostApPaymentJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Models\Account;
use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\Branch;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\User;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;

/**
 * Aktivitas AP bulanan untuk jendela demo 13 bulan (#571).
 *
 * Tiap bulan mendapat tagihan supplier (jurnal lewat PostSupplierBillJournalAction) dan
 * pembayaran confirmed yang dialokasikan ke tagihan bulan itu (PostApPaymentJournalAction).
 * Volume dan nilai mengikuti pola musiman DemoCalendar. Tanggal relatif terhadap tanggal seed.
 *
 * Idempoten: nomor deterministik (BILL-/PAY-YYYYMM-NNNN) dengan updateOrCreate.
 * Tagihan dan pembayaran yang sudah memuat jurnal dilepas lalu diposting ulang, sama
 * seperti ApSampleDataSeeder, agar nilai jurnal mengikuti dokumen terbaru.
 */
class ApMonthlyDemoSeeder extends Seeder
{
    public const BILL_PREFIX = 'BILL-';

    public const PAYMENT_PREFIX = 'PAY-';

    /** Dasar jumlah tagihan per bulan sebelum faktor volume. */
    private const BILLS_PER_MONTH = 3;

    /** Nilai dasar tagihan (IDR) sebelum faktor nilai bulan. */
    private const BASE_BILL_VALUE = 6000000;

    public function run(): void
    {
        // FY jendela berstatus open (CoaSeeder). Penutupan tahun lalu dilakukan sekali di
        // akhir seed oleh DemoPeriodClosingSeeder, setelah semua transaksi tercatat (#571).
        $this->seedWindow();
    }

    private function seedWindow(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id')
            ?? User::query()->value('id');
        $branch = Branch::query()->first();
        $suppliers = Supplier::query()->orderBy('id')->take(3)->get();
        $products = Product::query()->orderBy('id')->take(6)->get();
        $resolveControlAccount = app(ResolveControlAccountAction::class);

        if (! $adminUserId || ! $branch || $suppliers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $postBill = app(PostSupplierBillJournalAction::class);
        $postPayment = app(PostApPaymentJournalAction::class);

        foreach (DemoCalendar::months() as $index => $month) {
            $fiscalYear = FiscalYear::query()
                ->where('start_date', '<=', $month->toDateString())
                ->where('end_date', '>=', $month->toDateString())
                ->first();

            if ($fiscalYear === null) {
                continue;
            }

            $count = DemoCalendar::volume($index, self::BILLS_PER_MONTH);

            for ($n = 1; $n <= $count; $n++) {
                $supplier = $suppliers[($index + $n) % $suppliers->count()];
                $billDate = DemoCalendar::dateInMonth($index, 4 + $n * 4);
                $grandTotal = DemoCalendar::amount($index, self::BASE_BILL_VALUE * (0.7 + 0.25 * $n));
                $subtotal = round($grandTotal / 1.11, 2);
                $taxAmount = round($grandTotal - $subtotal, 2);
                $code = $month->format('Ym');

                $bill = SupplierBill::updateOrCreate(
                    ['bill_number' => sprintf('%s%s-%04d', self::BILL_PREFIX, $code, $n)],
                    [
                        'supplier_id' => $supplier->id,
                        'branch_id' => $branch->id,
                        'fiscal_year_id' => $fiscalYear->id,
                        'supplier_invoice_number' => sprintf('SINV-%s-%04d', $code, $n),
                        'supplier_invoice_date' => $billDate->toDateString(),
                        'bill_date' => $billDate->toDateString(),
                        'due_date' => $billDate->copy()->addDays(30)->toDateString(),
                        'payment_terms' => 'Net 30',
                        'currency' => 'IDR',
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'discount_amount' => 0,
                        'grand_total' => $grandTotal,
                        'amount_paid' => 0,
                        'amount_due' => $grandTotal,
                        'status' => 'confirmed',
                        'notes' => 'Tagihan supplier atas pembelian barang',
                        'created_by' => $adminUserId,
                        'confirmed_by' => $adminUserId,
                        'confirmed_at' => $billDate->copy()->addHours(2),
                    ],
                );

                // Akun beban dan bank harus dari COA yang sama dengan tanggal jurnalnya (#571).
                $expenseAccount = $this->expenseAccount($billDate->toDateString());
                $this->seedBillItem($bill, $products, $expenseAccount, $grandTotal, $index + $n);
                $postBill->execute($bill->refresh());

                $paymentDate = $billDate->copy()->addDays(5);
                $bankAccount = $resolveControlAccount->execute('11110', $paymentDate->toDateString());
                $payment = ApPayment::updateOrCreate(
                    ['payment_number' => sprintf('%s%s-%04d', self::PAYMENT_PREFIX, $code, $n)],
                    [
                        'supplier_id' => $supplier->id,
                        'branch_id' => $branch->id,
                        'fiscal_year_id' => $fiscalYear->id,
                        'payment_date' => $paymentDate->toDateString(),
                        'payment_method' => 'bank_transfer',
                        'bank_account_id' => $bankAccount->id,
                        'currency' => 'IDR',
                        'total_amount' => $grandTotal,
                        'total_allocated' => $grandTotal,
                        'total_unallocated' => 0,
                        'reference' => sprintf('REF-%s-%04d', $code, $n),
                        'status' => 'confirmed',
                        'notes' => 'Pembayaran tagihan supplier melalui transfer bank',
                        'created_by' => $adminUserId,
                        'confirmed_by' => $adminUserId,
                        'confirmed_at' => $paymentDate->copy()->addHours(1),
                    ],
                );

                ApPaymentAllocation::updateOrCreate(
                    ['ap_payment_id' => $payment->id, 'supplier_bill_id' => $bill->id],
                    [
                        'allocated_amount' => $grandTotal,
                        'discount_taken' => 0,
                        'notes' => 'Alokasi pembayaran ke tagihan supplier',
                    ],
                );

                $bill->forceFill([
                    'amount_paid' => $grandTotal,
                    'amount_due' => 0,
                ])->save();
                $bill->updatePaymentStatus();

                $postPayment->execute($payment->refresh());
            }
        }
    }

    /**
     * Akun beban pertama (daun) dari COA version yang berlaku pada tanggal bill (#571).
     */
    private function expenseAccount(string $entryDate): Account
    {
        $coaVersionId = app(ResolveControlAccountAction::class)->resolveVersion($entryDate)->id;

        return Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->firstOrFail();
    }

    private function seedBillItem(
        SupplierBill $bill,
        $products,
        Account $expenseAccount,
        float $grandTotal,
        int $seed,
    ): void {
        $product = $products[$seed % $products->count()];
        $quantity = 10;
        $unitPrice = round($grandTotal / $quantity, 2);

        SupplierBillItem::updateOrCreate(
            ['supplier_bill_id' => $bill->id, 'product_id' => $product->id],
            [
                'account_id' => $expenseAccount->id,
                'description' => $product->name . ' - pembelian bulanan',
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => 0,
                'tax_percent' => 11,
                'line_total' => round($unitPrice * $quantity, 2),
                'notes' => 'Item tagihan sesuai faktur supplier',
            ],
        );
    }
}
