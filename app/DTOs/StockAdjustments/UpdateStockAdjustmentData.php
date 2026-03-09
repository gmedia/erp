<?php

namespace App\DTOs\StockAdjustments;

readonly class UpdateStockAdjustmentData
{
    public function __construct(
        public ?string $adjustment_number = null,
        public ?int $warehouse_id = null,
        public ?string $adjustment_date = null,
        public ?string $adjustment_type = null,
        public ?string $status = null,
        public ?int $inventory_stocktake_id = null,
        public ?string $notes = null,
        public ?int $journal_entry_id = null,
        public ?int $approved_by = null,
        public ?string $approved_at = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            adjustment_number: $data['adjustment_number'] ?? null,
            warehouse_id: $data['warehouse_id'] ?? null,
            adjustment_date: $data['adjustment_date'] ?? null,
            adjustment_type: $data['adjustment_type'] ?? null,
            status: $data['status'] ?? null,
            inventory_stocktake_id: $data['inventory_stocktake_id'] ?? null,
            notes: $data['notes'] ?? null,
            journal_entry_id: $data['journal_entry_id'] ?? null,
            approved_by: $data['approved_by'] ?? null,
            approved_at: $data['approved_at'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->adjustment_number !== null) {
            $data['adjustment_number'] = $this->adjustment_number;
        }
        if ($this->warehouse_id !== null) {
            $data['warehouse_id'] = $this->warehouse_id;
        }
        if ($this->adjustment_date !== null) {
            $data['adjustment_date'] = $this->adjustment_date;
        }
        if ($this->adjustment_type !== null) {
            $data['adjustment_type'] = $this->adjustment_type;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->inventory_stocktake_id !== null) {
            $data['inventory_stocktake_id'] = $this->inventory_stocktake_id;
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }
        if ($this->journal_entry_id !== null) {
            $data['journal_entry_id'] = $this->journal_entry_id;
        }
        if ($this->approved_by !== null) {
            $data['approved_by'] = $this->approved_by;
        }
        if ($this->approved_at !== null) {
            $data['approved_at'] = $this->approved_at;
        }

        return $data;
    }
}
