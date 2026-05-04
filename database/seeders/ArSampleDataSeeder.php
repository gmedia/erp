<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\Branch;
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
use Illuminate\Database\Seeder;

class ArSampleDataSeeder extends Seeder
{
    public function run(): void
    {
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
        $revenueAccount = Account::query()
            ->where('type', 'revenue')
            ->first();

        if (! $branch || ! $fiscalYear || $products->isEmpty() || $units->isEmpty() || ! $revenueAccount) {
            return;
        }

        $customers = Customer::query()->take(5)->get();
        if ($customers->isEmpty()) {
            $categoryId = CustomerCategory::query()->value('id');
            foreach (['PT Maju Bersama', 'CV Sejahtera Abadi', 'PT Karya Mandiri', 'UD Sumber Rezeki', 'PT Global Teknik'] as $name) {
                Customer::create([
                    'name' => $name,
                    'email' => strtolower(str_replace([' ', '.'], ['', ''], $name)) . '@example.com',
                    'phone' => '08' . rand(1000000000, 9999999999),
                    'address' => 'Jl. Sample No. ' . rand(1, 100) . ', Jakarta',
                    'branch_id' => $branch->id,
                    'category_id' => $categoryId,
                    'status' => 'active',
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

        $invoiceDefinitions = [
            [
                'number' => "INV-{$year}-000001",
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
                'status' => 'partially_paid',
                'grand_total' => 35000000,
                'amount_received' => 15000000,
                'credit_note_amount' => 0,
                'item_count' => 3,
                'sent_by' => $adminUserId,
                'sent_at' => now()->subDays(10),
                'due_date_offset' => 30,
            ],
            [
                'number' => "INV-{$year}-000004",
                'status' => 'paid',
                'grand_total' => 18000000,
                'amount_received' => 18000000,
                'credit_note_amount' => 0,
                'item_count' => 2,
                'sent_by' => $adminUserId,
                'sent_at' => now()->subDays(15),
                'due_date_offset' => 30,
            ],
            [
                'number' => "INV-{$year}-000005",
                'status' => 'overdue',
                'grand_total' => 22000000,
                'amount_received' => 0,
                'credit_note_amount' => 0,
                'item_count' => 2,
                'sent_by' => $adminUserId,
                'sent_at' => now()->subDays(40),
                'due_date_offset' => -5,
            ],
            [
                'number' => "INV-{$year}-000006",
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
            $invoiceDate = now()->subDays(60 - $index * 5);
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
                    'amount_due' => $definition['grand_total'] - $definition['amount_received'] - $definition['credit_note_amount'],
                    'status' => $definition['status'],
                    'notes' => 'Sample customer invoice data',
                    'journal_entry_id' => null,
                    'created_by' => $adminUserId,
                    'sent_by' => $definition['sent_by'],
                    'sent_at' => $definition['sent_at'],
                ]
            );

            for ($i = 0; $i < $definition['item_count']; $i++) {
                $product = $products[($index + $i) % $products->count()];
                $unit = $units[($index + $i) % $units->count()];
                $quantity = ($i + 1) * 2;
                $unitPrice = ($definition['grand_total'] / $definition['item_count']) / $quantity;

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
                        'line_total' => $quantity * $unitPrice,
                        'account_id' => $revenueAccount->id,
                        'description' => 'Sample item',
                        'notes' => 'Sample invoice item',
                    ]
                );
            }

            $invoices[$definition['status']] = $invoice;
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

        $bankAccount = Account::query()
            ->where('code', '11110')
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
            $customer = $customers[$index % $customers->count()];
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
                    'notes' => 'Sample AR receipt data',
                    'journal_entry_id' => null,
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

                ArReceiptAllocation::updateOrCreate(
                    [
                        'ar_receipt_id' => $receipt->id,
                        'customer_invoice_id' => $invoice->id,
                    ],
                    [
                        'allocated_amount' => $allocationData['amount'],
                        'discount_given' => 0,
                        'notes' => 'Sample receipt allocation',
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

            $receipts[$definition['status'] . '_' . ($index + 1)] = $receipt;
        }

        return $receipts;
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
                    'status' => $definition['status'],
                    'notes' => 'Sample credit note data',
                    'journal_entry_id' => null,
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
                        'description' => 'Credit note item',
                        'notes' => 'Sample credit note item',
                    ]
                );
            }

            // Update invoice credit_note_amount if applied
            if ($definition['applied'] && $invoice) {
                $newCreditNoteAmount = (float) $invoice->credit_note_amount + $definition['grand_total'];
                $invoice->update([
                    'credit_note_amount' => $newCreditNoteAmount,
                    'amount_due' => $invoice->grand_total - $invoice->amount_received - $newCreditNoteAmount,
                ]);
                $invoice->updatePaymentStatus();
            }
        }
    }
}
