<?php

namespace App\DTOs\PurchaseOrders;

readonly class UpdatePurchaseOrderData
{
    public function __construct(
        public ?string $po_number = null,
        public ?int $supplier_id = null,
        public ?int $warehouse_id = null,
        public ?string $order_date = null,
        public ?string $expected_delivery_date = null,
        public ?string $payment_terms = null,
        public ?string $currency = null,
        public ?string $subtotal = null,
        public ?string $tax_amount = null,
        public ?string $discount_amount = null,
        public ?string $grand_total = null,
        public ?string $status = null,
        public ?string $notes = null,
        public ?string $shipping_address = null,
        public ?int $approved_by = null,
        public ?string $approved_at = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            po_number: $data['po_number'] ?? null,
            supplier_id: $data['supplier_id'] ?? null,
            warehouse_id: $data['warehouse_id'] ?? null,
            order_date: $data['order_date'] ?? null,
            expected_delivery_date: $data['expected_delivery_date'] ?? null,
            payment_terms: $data['payment_terms'] ?? null,
            currency: $data['currency'] ?? null,
            subtotal: isset($data['subtotal']) ? (string) $data['subtotal'] : null,
            tax_amount: isset($data['tax_amount']) ? (string) $data['tax_amount'] : null,
            discount_amount: isset($data['discount_amount']) ? (string) $data['discount_amount'] : null,
            grand_total: isset($data['grand_total']) ? (string) $data['grand_total'] : null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
            shipping_address: $data['shipping_address'] ?? null,
            approved_by: $data['approved_by'] ?? null,
            approved_at: $data['approved_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        $payload = [];

        if ($this->po_number !== null) {
            $payload['po_number'] = $this->po_number;
        }
        if ($this->supplier_id !== null) {
            $payload['supplier_id'] = $this->supplier_id;
        }
        if ($this->warehouse_id !== null) {
            $payload['warehouse_id'] = $this->warehouse_id;
        }
        if ($this->order_date !== null) {
            $payload['order_date'] = $this->order_date;
        }
        if ($this->expected_delivery_date !== null) {
            $payload['expected_delivery_date'] = $this->expected_delivery_date;
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
        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }
        if ($this->notes !== null) {
            $payload['notes'] = $this->notes;
        }
        if ($this->shipping_address !== null) {
            $payload['shipping_address'] = $this->shipping_address;
        }
        if ($this->approved_by !== null) {
            $payload['approved_by'] = $this->approved_by;
        }
        if ($this->approved_at !== null) {
            $payload['approved_at'] = $this->approved_at;
        }

        return $payload;
    }
}
