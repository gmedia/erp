<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Actions\AccountingPosting\PostSupplierBillJournalAction;
use App\Actions\AccountingPosting\ResolveControlAccountAction;
use App\Models\Account;
use App\Models\ApPaymentAllocation;
use App\Models\ArReceiptAllocation;
use App\Models\Branch;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\SupplierBill;
use App\Models\SupplierBillItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Dokumen AR/AP terbuka yang tersebar di seluruh bucket aging (#571 C8).
 *
 * Data demo sebelumnya hanya menyisakan tiga invoice dan dua bill terbuka, semuanya
 * di bucket 1–30 hari, sehingga laporan aging dan dasbor aging menampilkan bucket
 * 31–60, 61–90, dan >90 kosong. Seeder ini menambah invoice/bill terbuka dengan
 * jatuh tempo relatif terhadap tanggal seed: current, 1–30, 31–60, 61–90, dan >90.
 *
 * Jurnal tetap dibentuk lewat action posting aplikasi (bukan tulis langsung), sehingga
 * GL 11200/21100 sejalan dengan subledger. Idempoten: nomor deterministik
 * INV-{tahun}-0000NN / BILL-{tahun}-0000NN, dan saldo terbayar dipulihkan dari alokasi
 * yang ada agar pembayaran buatan pengguna tidak terhapus saat seed ulang.
 */
class DemoAgingSeeder extends Seeder
{
    /** Rentang jatuh tempo (hari dari hari ini) untuk tiap bucket aging. */
    private const INVOICE_OFFSETS = [
        'current' => [10, 24],
        '31_60' => [-42, -56],
        '61_90' => [-72, -86],
        'over_90' => [-102, -124],
    ];

    /** Rentang jatuh tempo (hari dari hari ini) untuk bill terbuka. */
    private const BILL_OFFSETS = [
        'current' => [14],
        '31_60' => [-45, -58],
        '61_90' => [-74, -88],
        'over_90' => [-106, -126],
    ];

    public function run(): void
    {
        $adminUserId = User::query()->where('email', config('app.admin'))->value('id')
            ?? User::query()->value('id');
        $branch = Branch::query()->first();
        $customers = Customer::query()->where('status', 'active')->orderBy('id')->take(5)->get();
        $suppliers = Supplier::query()->orderBy('id')->take(3)->get();
        $products = Product::query()->orderBy('id')->take(5)->get();
        $units = Unit::query()->orderBy('id')->take(3)->get();

        if (! $adminUserId || ! $branch || $customers->isEmpty() || $suppliers->isEmpty()
            || $products->isEmpty() || $units->isEmpty()) {
            return;
        }

        $this->seedOpenInvoices($adminUserId, $branch, $customers, $products, $units);
        $this->seedOpenBills($adminUserId, $branch, $suppliers, $products);
    }

    private function seedOpenInvoices(
        int $adminUserId,
        Branch $branch,
        $customers,
        $products,
        $units,
    ): void {
        $postJournal = app(PostCustomerInvoiceJournalAction::class);
        $resolveControlAccount = app(ResolveControlAccountAction::class);
        $today = Carbon::today();
        $sequence = 8;

        foreach (self::INVOICE_OFFSETS as $offsets) {
            foreach ($offsets as $offset) {
                $dueDate = $today->copy()->addDays($offset);
                $invoiceDate = $dueDate->copy()->subDays(30);
                $fiscalYear = $this->fiscalYearFor($invoiceDate);

                if ($fiscalYear === null) {
                    continue;
                }

                $customer = $customers[$sequence % $customers->count()];
                $product = $products[$sequence % $products->count()];
                $unit = $units[$sequence % $units->count()];
                $grandTotal = $this->invoiceAmount($offset);
                $subtotal = round($grandTotal / 1.1, 2);
                $taxAmount = round($grandTotal - $subtotal, 2);
                $status = $offset < 0 ? 'overdue' : 'sent';

                $invoice = CustomerInvoice::updateOrCreate(
                    ['invoice_number' => sprintf('INV-%s-%06d', $today->format('Y'), $sequence)],
                    [
                        'customer_id' => $customer->id,
                        'branch_id' => $branch->id,
                        'fiscal_year_id' => $fiscalYear->id,
                        'invoice_date' => $invoiceDate->toDateString(),
                        'due_date' => $dueDate->toDateString(),
                        'payment_terms' => 'Net 30',
                        'currency' => 'IDR',
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'discount_amount' => 0,
                        'grand_total' => $grandTotal,
                        'amount_received' => 0,
                        'credit_note_amount' => 0,
                        'amount_due' => $grandTotal,
                        'status' => $status,
                        'notes' => 'Invoice penjualan dengan termin pembayaran berjalan',
                        'created_by' => $adminUserId,
                        'sent_by' => $adminUserId,
                        'sent_at' => $invoiceDate->copy()->addDay()->setTime(9, 0),
                    ],
                );

                $this->seedInvoiceItem(
                    $invoice,
                    $product,
                    $unit,
                    $grandTotal,
                    $resolveControlAccount->resolveVersion($invoiceDate->toDateString())->id,
                );

                $this->restoreInvoiceSettlement($invoice, $today);
                $postJournal->execute($invoice->refresh());

                $sequence++;
            }
        }
    }

    private function seedOpenBills(
        int $adminUserId,
        Branch $branch,
        $suppliers,
        $products,
    ): void {
        $postJournal = app(PostSupplierBillJournalAction::class);
        $resolveControlAccount = app(ResolveControlAccountAction::class);
        $today = Carbon::today();
        $sequence = 8;

        foreach (self::BILL_OFFSETS as $offsets) {
            foreach ($offsets as $offset) {
                $dueDate = $today->copy()->addDays($offset);
                $billDate = $dueDate->copy()->subDays(30);
                $fiscalYear = $this->fiscalYearFor($billDate);

                if ($fiscalYear === null) {
                    continue;
                }

                $supplier = $suppliers[$sequence % $suppliers->count()];
                $product = $products[$sequence % $products->count()];
                $grandTotal = $this->billAmount($offset);
                $subtotal = round($grandTotal / 1.11, 2);
                $taxAmount = round($grandTotal - $subtotal, 2);
                $status = $offset < 0 ? 'overdue' : 'confirmed';

                $bill = SupplierBill::updateOrCreate(
                    ['bill_number' => sprintf('BILL-%s-%06d', $today->format('Y'), $sequence)],
                    [
                        'supplier_id' => $supplier->id,
                        'branch_id' => $branch->id,
                        'fiscal_year_id' => $fiscalYear->id,
                        'supplier_invoice_number' => sprintf('SINV-%s-%06d', $today->format('Ym'), $sequence),
                        'supplier_invoice_date' => $billDate->toDateString(),
                        'bill_date' => $billDate->toDateString(),
                        'due_date' => $dueDate->toDateString(),
                        'payment_terms' => 'Net 30',
                        'currency' => 'IDR',
                        'subtotal' => $subtotal,
                        'tax_amount' => $taxAmount,
                        'discount_amount' => 0,
                        'grand_total' => $grandTotal,
                        'amount_paid' => 0,
                        'amount_due' => $grandTotal,
                        'status' => $status,
                        'notes' => 'Tagihan supplier dengan termin pembayaran berjalan',
                        'created_by' => $adminUserId,
                        'confirmed_by' => $adminUserId,
                        'confirmed_at' => $billDate->copy()->addHours(2),
                    ],
                );

                $this->seedBillItem(
                    $bill,
                    $product,
                    $grandTotal,
                    $resolveControlAccount->resolveVersion($billDate->toDateString())->id,
                );

                $this->restoreBillSettlement($bill, $today);
                $postJournal->execute($bill->refresh());

                $sequence++;
            }
        }
    }

    private function fiscalYearFor(Carbon $date): ?FiscalYear
    {
        return FiscalYear::query()
            ->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString())
            ->first();
    }

    /**
     * Nilai invoice terbuka per bucket aging.
     *
     * Sebagian besar piutang demo harus belum jatuh tempo (current) dan hanya sebagian
     * kecil yang menunggak, sehingga rasio overdue AR berada di rentang wajar 15-30%
     * (#603 review host 2026-11-28). Nilai lama membuat hampir seluruh piutang overdue.
     */
    private function invoiceAmount(int $offset): float
    {
        return $offset >= 0 ? 100000000.0 : 2000000.0;
    }

    /**
     * Nilai tagihan terbuka per bucket aging. Sama seperti piutang: tagihan lancar
     * mendominasi, tagihan menunggak kecil tetapi tetap mengisi semua bucket.
     */
    private function billAmount(int $offset): float
    {
        return $offset >= 0 ? 90000000.0 : 1500000.0;
    }

    private function seedInvoiceItem(
        CustomerInvoice $invoice,
        Product $product,
        Unit $unit,
        float $grandTotal,
        int $coaVersionId,
    ): void {
        $revenueAccount = Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('type', 'revenue')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->firstOrFail();

        $quantity = 2;
        CustomerInvoiceItem::updateOrCreate(
            ['customer_invoice_id' => $invoice->id, 'product_id' => $product->id],
            [
                'unit_id' => $unit->id,
                'quantity' => $quantity,
                'unit_price' => round($grandTotal / $quantity, 2),
                'discount_percent' => 0,
                'tax_percent' => 10,
                'line_total' => $grandTotal,
                'account_id' => $revenueAccount->id,
                'description' => 'Penjualan barang sesuai pesanan pelanggan',
                'notes' => 'Item invoice dengan termin berjalan',
            ],
        );
    }

    private function seedBillItem(
        SupplierBill $bill,
        Product $product,
        float $grandTotal,
        int $coaVersionId,
    ): void {
        $expenseAccount = Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->firstOrFail();

        $quantity = 10;
        SupplierBillItem::updateOrCreate(
            ['supplier_bill_id' => $bill->id, 'product_id' => $product->id],
            [
                'account_id' => $expenseAccount->id,
                'description' => $product->name . ' - pembelian barang',
                'quantity' => $quantity,
                'unit_price' => round($grandTotal / $quantity, 2),
                'discount_percent' => 0,
                'tax_percent' => 11,
                'line_total' => $grandTotal,
                'notes' => 'Item tagihan dengan termin berjalan',
            ],
        );
    }

    /**
     * Pulihkan saldo invoice dari alokasi penerimaan dan credit note yang ada, lalu
     * tentukan status jatuh tempo. Pembayaran buatan pengguna tetap dihitung.
     */
    private function restoreInvoiceSettlement(CustomerInvoice $invoice, Carbon $today): void
    {
        $received = (float) ArReceiptAllocation::query()
            ->where('customer_invoice_id', $invoice->id)
            ->sum('allocated_amount');
        $credited = (float) CreditNote::query()
            ->where('customer_invoice_id', $invoice->id)
            ->where('status', 'applied')
            ->sum('grand_total');
        $settled = $received + $credited;
        $grandTotal = (float) $invoice->grand_total;

        $invoice->amount_received = $received;
        $invoice->credit_note_amount = $credited;
        $invoice->amount_due = round($grandTotal - $settled, 2);

        if ($grandTotal > 0 && $settled >= $grandTotal) {
            $invoice->status = 'paid';
        } elseif ($settled > 0) {
            $invoice->status = 'partially_paid';
        } else {
            $invoice->status = Carbon::parse($invoice->due_date)->lt($today) ? 'overdue' : 'sent';
        }

        $invoice->save();
    }

    /**
     * Pulihkan saldo bill dari alokasi pembayaran yang ada, lalu tentukan status
     * jatuh tempo. Pembayaran buatan pengguna tetap dihitung.
     */
    private function restoreBillSettlement(SupplierBill $bill, Carbon $today): void
    {
        $paid = (float) ApPaymentAllocation::query()
            ->where('supplier_bill_id', $bill->id)
            ->sum('allocated_amount');
        $grandTotal = (float) $bill->grand_total;

        $bill->amount_paid = $paid;
        $bill->amount_due = round($grandTotal - $paid, 2);

        if ($grandTotal > 0 && $paid >= $grandTotal) {
            $bill->status = 'paid';
        } elseif ($paid > 0) {
            $bill->status = 'partially_paid';
        } else {
            $bill->status = Carbon::parse($bill->due_date)->lt($today) ? 'overdue' : 'confirmed';
        }

        $bill->save();
    }
}
