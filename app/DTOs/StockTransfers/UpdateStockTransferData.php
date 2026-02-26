<?php

namespace App\DTOs\StockTransfers;

readonly class UpdateStockTransferData
{
    public function __construct(
        public ?string $transfer_number = null,
        public ?int $from_warehouse_id = null,
        public ?int $to_warehouse_id = null,
        public ?string $transfer_date = null,
        public ?string $expected_arrival_date = null,
        public ?string $status = null,
        public ?string $notes = null,
        public ?int $requested_by = null,
        public ?int $approved_by = null,
        public ?string $approved_at = null,
        public ?int $shipped_by = null,
        public ?string $shipped_at = null,
        public ?int $received_by = null,
        public ?string $received_at = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            transfer_number: $data['transfer_number'] ?? null,
            from_warehouse_id: $data['from_warehouse_id'] ?? null,
            to_warehouse_id: $data['to_warehouse_id'] ?? null,
            transfer_date: $data['transfer_date'] ?? null,
            expected_arrival_date: $data['expected_arrival_date'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
            requested_by: $data['requested_by'] ?? null,
            approved_by: $data['approved_by'] ?? null,
            approved_at: $data['approved_at'] ?? null,
            shipped_by: $data['shipped_by'] ?? null,
            shipped_at: $data['shipped_at'] ?? null,
            received_by: $data['received_by'] ?? null,
            received_at: $data['received_at'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->transfer_number !== null) {
            $data['transfer_number'] = $this->transfer_number;
        }
        if ($this->from_warehouse_id !== null) {
            $data['from_warehouse_id'] = $this->from_warehouse_id;
        }
        if ($this->to_warehouse_id !== null) {
            $data['to_warehouse_id'] = $this->to_warehouse_id;
        }
        if ($this->transfer_date !== null) {
            $data['transfer_date'] = $this->transfer_date;
        }
        if ($this->expected_arrival_date !== null) {
            $data['expected_arrival_date'] = $this->expected_arrival_date;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }
        if ($this->requested_by !== null) {
            $data['requested_by'] = $this->requested_by;
        }
        if ($this->approved_by !== null) {
            $data['approved_by'] = $this->approved_by;
        }
        if ($this->approved_at !== null) {
            $data['approved_at'] = $this->approved_at;
        }
        if ($this->shipped_by !== null) {
            $data['shipped_by'] = $this->shipped_by;
        }
        if ($this->shipped_at !== null) {
            $data['shipped_at'] = $this->shipped_at;
        }
        if ($this->received_by !== null) {
            $data['received_by'] = $this->received_by;
        }
        if ($this->received_at !== null) {
            $data['received_at'] = $this->received_at;
        }

        return $data;
    }
}
