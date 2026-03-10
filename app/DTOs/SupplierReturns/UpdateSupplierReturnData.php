<?php

namespace App\DTOs\SupplierReturns;

readonly class UpdateSupplierReturnData
{
    public function __construct(
        public ?string $return_number = null,
        public ?int $purchase_order_id = null,
        public ?int $goods_receipt_id = null,
        public ?int $supplier_id = null,
        public ?int $warehouse_id = null,
        public ?string $return_date = null,
        public ?string $reason = null,
        public ?string $status = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            return_number: $data['return_number'] ?? null,
            purchase_order_id: $data['purchase_order_id'] ?? null,
            goods_receipt_id: $data['goods_receipt_id'] ?? null,
            supplier_id: $data['supplier_id'] ?? null,
            warehouse_id: $data['warehouse_id'] ?? null,
            return_date: $data['return_date'] ?? null,
            reason: $data['reason'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        $payload = [];

        if ($this->return_number !== null) {
            $payload['return_number'] = $this->return_number;
        }
        if ($this->purchase_order_id !== null) {
            $payload['purchase_order_id'] = $this->purchase_order_id;
        }
        if ($this->goods_receipt_id !== null) {
            $payload['goods_receipt_id'] = $this->goods_receipt_id;
        }
        if ($this->supplier_id !== null) {
            $payload['supplier_id'] = $this->supplier_id;
        }
        if ($this->warehouse_id !== null) {
            $payload['warehouse_id'] = $this->warehouse_id;
        }
        if ($this->return_date !== null) {
            $payload['return_date'] = $this->return_date;
        }
        if ($this->reason !== null) {
            $payload['reason'] = $this->reason;
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
