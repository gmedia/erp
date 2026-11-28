<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostArReceiptJournalAction;
use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use App\Support\Demo\DemoCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Aktivitas AR bulanan untuk jendela demo 13 bulan (#571).
 *
 * Tiap bulan jendela mendapat invoice (status sent atau lebih, sehingga jurnal AR-nya
 * terbentuk) dan penerimaan confirmed yang dialokasikan ke invoice bulan itu. Volume dan
 * nilai mengikuti pola musiman di DemoCalendar. Tanggal relatif terhadap tanggal seed.
 *
 * Idempoten: nomor dokumen deterministik (INV/RCV-YYYYMM-NNNN), dibuat dengan updateOrCreate,
 * sehingga seed ulang memperbarui baris yang sama. Jurnal lewat action aplikasi.
 */
class ArMonthlyDemoSeeder extends Seeder
{
    /** Pola nomor dokumen bulanan. Dipakai juga untuk membersihkan baris lama. */
    public const INVOICE_PREFIX = 'INV-';

    public const RECEIPT_PREFIX = 'RCV-';

    /** Dasar jumlah invoice per bulan sebelum faktor volume. */
    private const INVOICES_PER_MONTH = 3;

    /**
     * Nilai dasar invoice rata-rata (IDR) sebelum faktor nilai bulan.
     *
     * Nilai ini menentukan pendapatan demo. Agar perusahaan demo sehat, pendapatan harus
     * menutup beban tetap (penyusutan dari register aset + beban umum) di hampir semua
     * bulan, bukan hanya di bulan puncak (#571 review host C7).
     */
    private const BASE_INVOICE_VALUE = 20000000;

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
        $products = Product::query()->orderBy('id')->take(5)->get();
        $units = Unit::query()->orderBy('id')->take(3)->get();
        $customers = Customer::query()->where('status', 'active')->orderBy('id')->take(5)->get();

        if (! $adminUserId || ! $branch || $products->isEmpty() || $units->isEmpty()
            || $customers->isEmpty()) {
            return;
        }

        foreach (DemoCalendar::months() as $index => $month) {
            $fiscalYear = FiscalYear::query()
                ->where('start_date', '<=', $month->toDateString())
                ->where('end_date', '>=', $month->toDateString())
                ->first();

            if ($fiscalYear === null) {
                continue;
            }

            $this->seedMonth(
                $index,
                $month,
                $fiscalYear,
                $adminUserId,
                $branch,
                $customers,
                $products,
                $units,
            );
        }
    }

    private function seedMonth(
        int $index,
        Carbon $month,
        FiscalYear $fiscalYear,
        int $adminUserId,
        Branch $branch,
        $customers,
        $products,
        $units,
    ): void {
        $postInvoice = app(PostCustomerInvoiceJournalAction::class);
        $postReceipt = app(PostArReceiptJournalAction::class);
        $resolveControlAccount = app(ResolveControlAccountAction::class);
        $code = $month->format('Ym');
        $count = DemoCalendar::volume($index, self::INVOICES_PER_MONTH);

        for ($n = 1; $n <= $count; $n++) {
            $customer = $customers[($index + $n) % $customers->count()];
            // Tanggal invoice dan penerimaan tetap di bulan yang sama (penerimaan 5 hari
            // setelah invoice) agar jumlah per bulan konsisten dengan laporan.
            $invoiceDate = DemoCalendar::dateInMonth($index, 3 + $n * 4);
            $grandTotal = DemoCalendar::amount($index, self::BASE_INVOICE_VALUE * (0.6 + 0.2 * $n));
            $subtotal = round($grandTotal / 1.1, 2);
            $taxAmount = round($grandTotal - $subtotal, 2);

            $invoice = CustomerInvoice::updateOrCreate(
                ['invoice_number' => sprintf('%s%s-%04d', self::INVOICE_PREFIX, $code, $n)],
                [
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'invoice_date' => $invoiceDate->toDateString(),
                    'due_date' => $invoiceDate->copy()->addDays(30)->toDateString(),
                    'payment_terms' => 'Net 30',
                    'currency' => 'IDR',
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => 0,
                    'grand_total' => $grandTotal,
                    'amount_received' => 0,
                    'credit_note_amount' => 0,
                    'amount_due' => $grandTotal,
                    'status' => 'sent',
                    'notes' => 'Invoice penjualan bulanan sesuai pesanan pelanggan',
                    'created_by' => $adminUserId,
                    'sent_by' => $adminUserId,
                    'sent_at' => $invoiceDate->copy()->addDay()->setTime(10, 0),
                ],
            );

            // Akun pendapatan dari COA yang berlaku pada tanggal invoice (#571).
            $revenueAccount = $this->revenueAccount($invoiceDate->toDateString());
            $this->seedInvoiceItems($invoice, $grandTotal, $index + $n, $products, $units, $revenueAccount);
            $postInvoice->execute($invoice->refresh());

            $receiptDate = $invoiceDate->copy()->addDays(5);
            // Akun bank (11110) dari COA yang berlaku pada tanggal penerimaan (#571).
            $bankAccount = $resolveControlAccount->execute('11110', $receiptDate->toDateString());

            $receipt = ArReceipt::updateOrCreate(
                ['receipt_number' => sprintf('%s%s-%04d', self::RECEIPT_PREFIX, $code, $n)],
                [
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'receipt_date' => $receiptDate->toDateString(),
                    'payment_method' => 'bank_transfer',
                    'bank_account_id' => $bankAccount->id,
                    'currency' => 'IDR',
                    'total_amount' => $grandTotal,
                    'total_allocated' => $grandTotal,
                    'total_unallocated' => 0,
                    'reference' => sprintf('TRF-%s-%04d', $code, $n),
                    'status' => 'confirmed',
                    'notes' => 'Penerimaan pembayaran melalui transfer bank',
                    'created_by' => $adminUserId,
                    'confirmed_by' => $adminUserId,
                    'confirmed_at' => $invoiceDate->copy()->addDays(5)->setTime(14, 0),
                ],
            );

            ArReceiptAllocation::updateOrCreate(
                ['ar_receipt_id' => $receipt->id, 'customer_invoice_id' => $invoice->id],
                [
                    'allocated_amount' => $grandTotal,
                    'discount_given' => 0,
                    'notes' => 'Alokasi pembayaran ke invoice terkait',
                ],
            );

            $invoice->forceFill([
                'amount_received' => $grandTotal,
                'amount_due' => 0,
            ])->save();
            $invoice->updatePaymentStatus();

            $postReceipt->execute($receipt->refresh());
        }
    }

    /**
     * Akun pendapatan pertama (daun) dari COA version yang berlaku pada tanggal invoice (#571).
     */
    private function revenueAccount(string $entryDate): Account
    {
        $coaVersionId = app(ResolveControlAccountAction::class)->resolveVersion($entryDate)->id;

        return Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('type', 'revenue')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->firstOrFail();
    }

    private function seedInvoiceItems(
        CustomerInvoice $invoice,
        float $grandTotal,
        int $seed,
        $products,
        $units,
        Account $revenueAccount,
    ): void {
        $itemCount = 2;
        $lineBase = round($grandTotal / $itemCount, 2);
        $lineRemainder = round($grandTotal - $lineBase * $itemCount, 2);

        for ($i = 0; $i < $itemCount; $i++) {
            $product = $products[($seed + $i) % $products->count()];
            $unit = $units[($seed + $i) % $units->count()];
            $quantity = $i + 2;
            $lineTotal = $i === $itemCount - 1 ? round($lineBase + $lineRemainder, 2) : $lineBase;

            CustomerInvoiceItem::updateOrCreate(
                ['customer_invoice_id' => $invoice->id, 'product_id' => $product->id],
                [
                    'unit_id' => $unit->id,
                    'quantity' => $quantity,
                    'unit_price' => round($lineTotal / $quantity, 2),
                    'discount_percent' => 0,
                    'tax_percent' => 10,
                    'line_total' => $lineTotal,
                    'account_id' => $revenueAccount->id,
                    'description' => 'Penjualan barang sesuai pesanan',
                    'notes' => 'Item invoice sesuai pesanan pelanggan',
                ],
            );
        }
    }
}
