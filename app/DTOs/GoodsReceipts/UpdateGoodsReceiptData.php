<?php

namespace App\DTOs\GoodsReceipts;

readonly class UpdateGoodsReceiptData
{
    public function __construct(
        public ?string $gr_number = null,
        public ?int $purchase_order_id = null,
        public ?int $warehouse_id = null,
        public ?string $receipt_date = null,
        public ?string $supplier_delivery_note = null,
        public ?string $status = null,
        public ?string $notes = null,
        public ?int $received_by = null,
        public ?int $confirmed_by = null,
        public ?string $confirmed_at = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            gr_number: $data['gr_number'] ?? null,
            purchase_order_id: $data['purchase_order_id'] ?? null,
            warehouse_id: $data['warehouse_id'] ?? null,
            receipt_date: $data['receipt_date'] ?? null,
            supplier_delivery_note: $data['supplier_delivery_note'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
            received_by: $data['received_by'] ?? null,
            confirmed_by: $data['confirmed_by'] ?? null,
            confirmed_at: $data['confirmed_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        $payload = [];

        if ($this->gr_number !== null) {
            $payload['gr_number'] = $this->gr_number;
        }
        if ($this->purchase_order_id !== null) {
            $payload['purchase_order_id'] = $this->purchase_order_id;
        }
        if ($this->warehouse_id !== null) {
            $payload['warehouse_id'] = $this->warehouse_id;
        }
        if ($this->receipt_date !== null) {
            $payload['receipt_date'] = $this->receipt_date;
        }
        if ($this->supplier_delivery_note !== null) {
            $payload['supplier_delivery_note'] = $this->supplier_delivery_note;
        }
        if ($this->status !== null) {
            $payload['status'] = $this->status;
        }
        if ($this->notes !== null) {
            $payload['notes'] = $this->notes;
        }
        if ($this->received_by !== null) {
            $payload['received_by'] = $this->received_by;
        }
        if ($this->confirmed_by !== null) {
            $payload['confirmed_by'] = $this->confirmed_by;
        }
        if ($this->confirmed_at !== null) {
            $payload['confirmed_at'] = $this->confirmed_at;
        }

        return $payload;
    }
}
