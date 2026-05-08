<?php

namespace App\DTOs\SupplierBills;

readonly class UpdateSupplierBillData
{
    public function __construct(
        public ?string $bill_number = null,
        public ?int $supplier_id = null,
        public ?int $branch_id = null,
        public ?int $fiscal_year_id = null,
        public ?int $purchase_order_id = null,
        public ?int $goods_receipt_id = null,
        public ?string $supplier_invoice_number = null,
        public ?string $supplier_invoice_date = null,
        public ?string $bill_date = null,
        public ?string $due_date = null,
        public ?string $payment_terms = null,
        public ?string $currency = null,
        public ?string $subtotal = null,
        public ?string $tax_amount = null,
        public ?string $discount_amount = null,
        public ?string $grand_total = null,
        public ?string $amount_paid = null,
        public ?string $amount_due = null,
        public ?string $status = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            bill_number: $data['bill_number'] ?? null,
            supplier_id: $data['supplier_id'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            fiscal_year_id: $data['fiscal_year_id'] ?? null,
            purchase_order_id: $data['purchase_order_id'] ?? null,
            goods_receipt_id: $data['goods_receipt_id'] ?? null,
            supplier_invoice_number: $data['supplier_invoice_number'] ?? null,
            supplier_invoice_date: $data['supplier_invoice_date'] ?? null,
            bill_date: $data['bill_date'] ?? null,
            due_date: $data['due_date'] ?? null,
            payment_terms: $data['payment_terms'] ?? null,
            currency: $data['currency'] ?? null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : null,
            tax_amount: isset($data['tax_amount']) ? (string) $data['tax_amount'] : null,
            discount_amount: isset($data['discount_amount']) ? (string) $data['discount_amount'] : null,
            grand_total: isset($data['grand_total']) ? (string) $data['grand_total'] : null,
            amount_paid: isset($data['amount_paid']) ? (string) $data['amount_paid'] : null,
            amount_due: isset($data['amount_due']) ? (string) $data['amount_due'] : null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        $payload = [];

        if ($this->bill_number !== null) {
            $payload['bill_number'] = $this->bill_number;
        }
        if ($this->supplier_id !== null) {
            $payload['supplier_id'] = $this->supplier_id;
        }
        if ($this->branch_id !== null) {
            $payload['branch_id'] = $this->branch_id;
        }
        if ($this->fiscal_year_id !== null) {
            $payload['fiscal_year_id'] = $this->fiscal_year_id;
        }
        if ($this->purchase_order_id !== null) {
            $payload['purchase_order_id'] = $this->purchase_order_id;
        }
        if ($this->goods_receipt_id !== null) {
            $payload['goods_receipt_id'] = $this->goods_receipt_id;
        }
        if ($this->supplier_invoice_number !== null) {
            $payload['supplier_invoice_number'] = $this->supplier_invoice_number;
        }
        if ($this->supplier_invoice_date !== null) {
            $payload['supplier_invoice_date'] = $this->supplier_invoice_date;
        }
        if ($this->bill_date !== null) {
            $payload['bill_date'] = $this->bill_date;
        }
        if ($this->due_date !== null) {
            $payload['due_date'] = $this->due_date;
        }
        if ($this->payment_terms !== null) {
            $payload['payment_terms'] = $this->payment_terms;
        }
        if ($this->currency !== null) {
            $payload['currency'] = $this->currency;
        }
        if ($this->subtotal !== null) {
            $payload['subtotal'] = $this->subtotal;
        }
        if ($this->tax_amount !== null) {
            $payload['tax_amount'] = $this->tax_amount;
        }
        if ($this->discount_amount !== null) {
            $payload['discount_amount'] = $this->discount_amount;
        }
        if ($this->grand_total !== null) {
            $payload['grand_total'] = $this->grand_total;
        }
        if ($this->amount_paid !== null) {
            $payload['amount_paid'] = $this->amount_paid;
        }
        if ($this->amount_due !== null) {
            $payload['amount_due'] = $this->amount_due;
        }
        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }
        if ($this->notes !== null) {
            $payload['notes'] = $this->notes;
        }

        return $payload;
    }
}
