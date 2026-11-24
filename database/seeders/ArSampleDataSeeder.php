<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostArReceiptJournalAction;
use App\Actions\AccountingPosting\PostCustomerInvoiceJournalAction;
use App\Actions\CreditNotes\ApplyCreditNoteAction;
use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Branch;
use App\Models\CoaVersion;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\CustomerInvoice;
use App\Models\CustomerInvoiceItem;
use App\Models\FiscalYear;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\Concerns\ReopensDemoPeriodClosings;
use Illuminate\Database\Seeder;

class ArSampleDataSeeder extends Seeder
{
    use ReopensDemoPeriodClosings;

    /**
     * Status invoice yang pernah dikirim sehingga jurnal AR-nya sudah diposting
     * (sama seperti CustomerInvoiceController saat transisi ke "sent").
     */
    private const POSTED_INVOICE_STATUSES = ['sent', 'partially_paid', 'paid', 'overdue'];

    protected function seedDemoData(): void
    {
        Customer::where('address', 'like', 'Jl. Sample No.%')
            ->get()
            ->each(function (Customer $customer): void {
                $customer->update([
                    'address' => str_replace('Jl. Sample No.', 'Jl. Sudirman No.', (string) $customer->address),
                ]);
            });

        $adminUserId = User::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? User::query()->value('id');

        if (! $adminUserId) {
            return;
        }

        $branch = Branch::query()->first();
        $fiscalYear = FiscalYear::query()->where('status', 'open')->first();
        $products = Product::query()->take(5)->get();
        $units = Unit::query()->take(3)->get();
        $activeCoaVersionId = CoaVersion::query()->where('status', 'active')->value('id');
        $revenueAccount = Account::query()
            ->where('coa_version_id', $activeCoaVersionId)
            ->where('type', 'revenue')
            ->where('is_active', true)
            ->orderBy('code')
            ->first();

        if (! $branch || ! $fiscalYear || $products->isEmpty() || $units->isEmpty() || ! $revenueAccount) {
            return;
        }

        $customers = Customer::query()->take(5)->get();
        if ($customers->isEmpty()) {
            // Cabang, kategori, dan status bervariasi agar data demo tidak seragam (W7-17).
            $categoryIds = CustomerCategory::query()->orderBy('id')->pluck('id')->all();
            $branchIds = Branch::query()->orderBy('id')->pluck('id')->all();
            $statuses = ['active', 'active', 'inactive'];

            foreach (['PT Maju Bersama', 'CV Sejahtera Abadi', 'PT Karya Mandiri', 'UD Sumber Rezeki', 'PT Global Teknik'] as $index => $name) {
                Customer::create([
                    'name' => $name,
                    'email' => strtolower(str_replace([' ', '.'], ['', ''], $name)) . '@example.com',
                    'phone' => '0812' . str_pad((string) (1000000 + $index * 137), 7, '0', STR_PAD_LEFT),
                    'address' => 'Jl. Sudirman No. ' . (10 + $index * 7) . ', Jakarta',
                    'branch_id' => $branchIds[$index % count($branchIds)] ?? $branch->id,
                    'category_id' => $categoryIds[$index % count($categoryIds)] ?? null,
                    'status' => $statuses[$index % count($statuses)],
                ]);
            }
            $customers = Customer::query()->take(5)->get();
        }

        $year = now()->format('Y');

        $invoices = $this->seedCustomerInvoices(
            $adminUserId,
            $branch,
            $fiscalYear,
            $customers,
            $products,
            $units,
            $revenueAccount,
            $year
        );

        $this->seedArReceipts(
            $adminUserId,
            $branch,
            $fiscalYear,
            $customers,
            $invoices,
            $year
        );

        $this->seedCreditNotes(
            $adminUserId,
            $branch,
            $fiscalYear,
            $customers,
            $invoices,
            $products,
            $units,
            $revenueAccount,
            $year
        );
    }

    private function seedCustomerInvoices(
        int $adminUserId,
        Branch $branch,
        FiscalYear $fiscalYear,
        $customers,
        $products,
        $units,
        Account $revenueAccount,
        string $year
    ): array {
        $invoices = [];
        $postJournal = app(PostCustomerInvoiceJournalAction::class);

        $invoiceDefinitions = [
            [
                'number' => "INV-{$year}-000001",
                'invoice_date_days_ago' => 40,
                'status' => 'draft',
                'grand_total' => 12000000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'item_count' => 3,
                'sent_by' => null,
                'sent_at' => null,
                'due_date_offset' => 30,
            ],
            [
                'number' => "INV-{$year}-000002",
                'invoice_date_days_ago' => 39,
                'status' => 'sent',
                'grand_total' => 28000000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'item_count' => 4,
                'sent_by' => $adminUserId,
                'sent_at' => now()->subDays(5),
                'due_date_offset' => 30,
            ],
            [
                'number' => "INV-{$year}-000003",
                'invoice_date_days_ago' => 38,
                'status' => 'partially_paid',
                'grand_total' => 35000000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'item_count' => 3,
                'sent_by' => $adminUserId,
                'sent_at' => now()->subDays(10),
                'due_date_offset' => 30,
            ],
            [
                'number' => "INV-{$year}-000004",
                'invoice_date_days_ago' => 37,
                'status' => 'paid',
                'grand_total' => 18000000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'item_count' => 2,
                'sent_by' => $adminUserId,
                'sent_at' => now()->subDays(15),
                'due_date_offset' => 30,
            ],
            [
                'number' => "INV-{$year}-000005",
                'invoice_date_days_ago' => 60,
                'status' => 'overdue',
                'grand_total' => 22000000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'item_count' => 2,
                'sent_by' => $adminUserId,
                'sent_at' => now()->subDays(40),
                'due_date_offset' => 30,
            ],
            [
                'number' => "INV-{$year}-000006",
                'invoice_date_days_ago' => 35,
                'status' => 'cancelled',
                'grand_total' => 10000000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'item_count' => 2,
                'sent_by' => null,
                'sent_at' => null,
                'due_date_offset' => 30,
            ],
            [
                'number' => "INV-{$year}-000007",
                'invoice_date_days_ago' => 34,
                'status' => 'void',
                'grand_total' => 16000000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'item_count' => 3,
                'sent_by' => $adminUserId,
                'sent_at' => now()->subDays(20),
                'due_date_offset' => 30,
            ],
        ];

        foreach ($invoiceDefinitions as $index => $definition) {
            $customer = $customers[$index % $customers->count()];
            // Tanggal dokumen lebih tua untuk invoice overdue agar jatuh
            // tempo tetap setelah tanggal dokumen (W5-20).
            $invoiceDate = now()->subDays(
                $definition['invoice_date_days_ago'] ?? (60 - $index * 5),
            );
            $dueDate = $invoiceDate->copy()->addDays($definition['due_date_offset']);

            $subtotal = $definition['grand_total'] * 0.9;
            $taxAmount = $definition['grand_total'] - $subtotal;

            $invoice = CustomerInvoice::updateOrCreate(
                ['invoice_number' => $definition['number']],
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
                    'grand_total' => $definition['grand_total'],
                    'amount_received' => $definition['amount_received'],
                    'credit_note_amount' => $definition['credit_note_amount'],
                    'amount_due' => in_array($definition['status'], ['draft', 'cancelled', 'void'], true)
                        ? 0
                        : $definition['grand_total'] - $definition['amount_received'] - $definition['credit_note_amount'],
                    'status' => $definition['status'],
                    'notes' => 'Invoice penjualan sesuai pesanan pelanggan',
                    'created_by' => $adminUserId,
                    'sent_by' => $definition['sent_by'],
                    'sent_at' => $definition['sent_at'],
                ]
            );

            // Total baris dibulatkan ke rupiah penuh; selisih pembulatan dibebankan
            // ke baris terakhir agar Σ line_total = grand_total (jurnal AR = invoice).
            $lineTotalBase = round($definition['grand_total'] / $definition['item_count'], 2);
            $lineTotalRemainder = round($definition['grand_total'] - $lineTotalBase * $definition['item_count'], 2);

            for ($i = 0; $i < $definition['item_count']; $i++) {
                $product = $products[($index + $i) % $products->count()];
                $unit = $units[($index + $i) % $units->count()];
                $quantity = ($i + 1) * 2;
                $lineTotal = $i === $definition['item_count'] - 1
                    ? round($lineTotalBase + $lineTotalRemainder, 2)
                    : $lineTotalBase;
                $unitPrice = round($lineTotal / $quantity, 2);

                CustomerInvoiceItem::updateOrCreate(
                    [
                        'customer_invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'unit_id' => $unit->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'discount_percent' => 0,
                        'tax_percent' => 10,
                        'line_total' => $lineTotal,
                        'account_id' => $revenueAccount->id,
                        'description' => 'Penjualan barang sesuai pesanan',
                        'notes' => 'Item invoice sesuai pesanan pelanggan',
                    ]
                );
            }

            if (in_array($definition['status'], self::POSTED_INVOICE_STATUSES, true)) {
                $postJournal->execute($invoice->refresh());
            }

            $invoices[$definition['status']] = $invoice->refresh();
        }

        return $invoices;
    }

    private function seedArReceipts(
        int $adminUserId,
        Branch $branch,
        FiscalYear $fiscalYear,
        $customers,
        array $invoices,
        string $year
    ): array {
        $receipts = [];
        $postReceiptJournal = app(PostArReceiptJournalAction::class);

        $bankAccount = Account::query()
            ->where('coa_version_id', CoaVersion::query()->where('status', 'active')->value('id'))
            ->where('code', '11110')
            ->where('is_active', true)
            ->first();

        if (! $bankAccount) {
            return [];
        }

        $receiptDefinitions = [
            [
                'number' => "RCV-{$year}-000001",
                'status' => 'draft',
                'total_amount' => 15000000,
                'total_allocated' => 0,
                'allocations' => [],
            ],
            [
                'number' => "RCV-{$year}-000002",
                'status' => 'confirmed',
                'total_amount' => 18000000,
                'total_allocated' => 18000000,
                'allocations' => [
                    ['invoice' => 'paid', 'amount' => 18000000],
                ],
            ],
            [
                'number' => "RCV-{$year}-000003",
                'status' => 'confirmed',
                'total_amount' => 20000000,
                'total_allocated' => 20000000,
                'allocations' => [
                    ['invoice' => 'partially_paid', 'amount' => 5000000],
                    ['invoice' => 'sent', 'amount' => 15000000],
                ],
            ],
        ];

        foreach ($receiptDefinitions as $index => $definition) {
            // Pihak penerimaan = pihak invoice yang dialokasikan (AR allocation harus satu customer).
            $allocatedInvoice = $invoices[$definition['allocations'][0]['invoice'] ?? ''] ?? null;
            $customer = $allocatedInvoice !== null
                ? $customers->firstWhere('id', $allocatedInvoice->customer_id)
                : $customers[$index % $customers->count()];
            $receiptDate = now()->subDays(30 - $index * 3);

            $receipt = ArReceipt::updateOrCreate(
                ['receipt_number' => $definition['number']],
                [
                    'customer_id' => $customer->id,
                    'branch_id' => $branch->id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'receipt_date' => $receiptDate->toDateString(),
                    'payment_method' => 'bank_transfer',
                    'bank_account_id' => $bankAccount->id,
                    'currency' => 'IDR',
                    'total_amount' => $definition['total_amount'],
                    'total_allocated' => $definition['total_allocated'],
                    'total_unallocated' => $definition['total_amount'] - $definition['total_allocated'],
                    'reference' => 'TRF-' . now()->format('Ymd') . '-' . ($index + 1),
                    'status' => $definition['status'],
                    'notes' => 'Penerimaan pembayaran melalui transfer bank',
                    'created_by' => $adminUserId,
                    'confirmed_by' => $definition['status'] === 'confirmed' ? $adminUserId : null,
                    'confirmed_at' => $definition['status'] === 'confirmed' ? now()->subDays(2) : null,
                ]
            );

            foreach ($definition['allocations'] as $allocationData) {
                $invoice = $invoices[$allocationData['invoice']] ?? null;
                if (! $invoice) {
                    continue;
                }

                // Satu penerimaan hanya boleh mengalokasikan invoice milik pihak yang sama.
                if ((int) $invoice->customer_id !== (int) $receipt->customer_id) {
                    $invoice->update(['customer_id' => $receipt->customer_id]);
                    $invoice->refresh();
                }

                ArReceiptAllocation::updateOrCreate(
                    [
                        'ar_receipt_id' => $receipt->id,
                        'customer_invoice_id' => $invoice->id,
                    ],
                    [
                        'allocated_amount' => $allocationData['amount'],
                        'discount_given' => 0,
                        'notes' => 'Alokasi pembayaran ke invoice terkait',
                    ]
                );

                if ($definition['status'] === 'confirmed') {
                    $newAmountReceived = (float) $invoice->amount_received + $allocationData['amount'];
                    $invoice->update([
                        'amount_received' => $newAmountReceived,
                        'amount_due' => $invoice->grand_total - $newAmountReceived - $invoice->credit_note_amount,
                    ]);
                    $invoice->updatePaymentStatus();
                }
            }

            if ($definition['status'] === 'confirmed') {
                $postReceiptJournal->execute($receipt->refresh());
            }

            $receipts[$definition['status'] . '_' . ($index + 1)] = $receipt;
        }

        return $receipts;
    }

    /**
     * Menyelaraskan credit_note_amount/amount_due invoice dengan CN applied dan
     * penerimaan yang tersisa. Rumus sama dengan ApplyCreditNoteAction.
     */
    private function restoreInvoiceSettlement(CustomerInvoice $invoice): void
    {
        $credited = (float) CreditNote::query()
            ->where('customer_invoice_id', $invoice->id)
            ->where('status', 'applied')
            ->sum('grand_total');

        $invoice->credit_note_amount = $credited;
        $invoice->amount_due = (float) $invoice->grand_total
            - (float) $invoice->amount_received
            - $credited;
        $invoice->save();
        $invoice->updatePaymentStatus();
    }

    private function seedCreditNotes(
        int $adminUserId,
        Branch $branch,
        FiscalYear $fiscalYear,
        $customers,
        array $invoices,
        $products,
        $units,
        Account $revenueAccount,
        string $year
    ): void {
        $creditNoteDefinitions = [
            [
                'number' => "CN-{$year}-000001",
                'status' => 'draft',
                'reason' => 'return',
                'grand_total' => 3000000,
                'invoice_status' => 'sent',
                'applied' => false,
            ],
            [
                'number' => "CN-{$year}-000002",
                'status' => 'confirmed',
                'reason' => 'discount',
                'grand_total' => 5000000,
                'invoice_status' => 'partially_paid',
                'applied' => false,
            ],
            [
                'number' => "CN-{$year}-000003",
                'status' => 'applied',
                'reason' => 'correction',
                'grand_total' => 2000000,
                'invoice_status' => 'overdue',
                'applied' => true,
            ],
        ];

        foreach ($creditNoteDefinitions as $index => $definition) {
            $customer = $customers[$index % $customers->count()];
            $invoice = $invoices[$definition['invoice_status']] ?? null;
            $creditNoteDate = now()->subDays(20 - $index * 2);

            $subtotal = $definition['grand_total'] * 0.9;
            $taxAmount = $definition['grand_total'] - $subtotal;

            // CN "applied" dibuat confirmed dulu; status applied diset oleh ApplyCreditNoteAction.
            // Seed ulang tidak boleh menurunkan CN yang sudah applied kembali ke confirmed.
            $existingStatus = CreditNote::query()
                ->where('credit_note_number', $definition['number'])
                ->value('status');
            $targetStatus = $definition['applied'] ? 'confirmed' : $definition['status'];
            if ($existingStatus === 'applied') {
                $targetStatus = 'applied';
            }

            $creditNote = CreditNote::updateOrCreate(
                ['credit_note_number' => $definition['number']],
                [
                    'customer_id' => $customer->id,
                    'customer_invoice_id' => $invoice?->id,
                    'branch_id' => $branch->id,
                    'fiscal_year_id' => $fiscalYear->id,
                    'credit_note_date' => $creditNoteDate->toDateString(),
                    'reason' => $definition['reason'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'grand_total' => $definition['grand_total'],
                    'status' => $targetStatus,
                    'notes' => 'Nota kredit atas retur penjualan',
                    'created_by' => $adminUserId,
                    'confirmed_by' => in_array($definition['status'], ['confirmed', 'applied'], true) ? $adminUserId : null,
                    'confirmed_at' => in_array($definition['status'], ['confirmed', 'applied'], true) ? now()->subDays(1) : null,
                ]
            );

            $itemCount = $index % 2 + 1;
            for ($i = 0; $i < $itemCount; $i++) {
                $product = $products[($index + $i) % $products->count()];
                $unit = $units[($index + $i) % $units->count()];
                $quantity = ($i + 1);
                $unitPrice = $definition['grand_total'] / $itemCount / $quantity;

                CreditNoteItem::updateOrCreate(
                    [
                        'credit_note_id' => $creditNote->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'tax_percent' => 10,
                        'line_total' => $quantity * $unitPrice,
                        'account_id' => $revenueAccount->id,
                        'description' => 'Item nota kredit retur penjualan',
                        'notes' => 'Item nota kredit atas retur penjualan',
                    ]
                );
            }

            // Credit note applied: lewat ApplyCreditNoteAction, yang memposting jurnal
            // (PostCreditNoteJournalAction) dan memperbarui credit_note_amount/amount_due invoice.
            if ($definition['applied'] && $invoice && $creditNote->fresh()->status === 'confirmed') {
                app(ApplyCreditNoteAction::class)->execute($creditNote->fresh());
            }

            // Seed ulang: invoice di-upsert dengan credit_note_amount = 0, sehingga CN yang sudah
            // applied (dan jurnalnya) tidak lagi tercermin di invoice. Pulihkan dari CN applied.
            if ($definition['applied'] && $invoice) {
                $this->restoreInvoiceSettlement($invoice);
            }
        }
    }
}
