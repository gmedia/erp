<?php

namespace App\DTOs\InventoryStocktakes;

readonly class UpdateInventoryStocktakeData
{
    public function __construct(
        public ?string $stocktake_number = null,
        public ?int $warehouse_id = null,
        public ?string $stocktake_date = null,
        public ?string $status = null,
        public ?int $product_category_id = null,
        public ?string $notes = null,
        public ?int $completed_by = null,
        public ?string $completed_at = null,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            stocktake_number: $data['stocktake_number'] ?? null,
            warehouse_id: $data['warehouse_id'] ?? null,
            stocktake_date: $data['stocktake_date'] ?? null,
            status: $data['status'] ?? null,
            product_category_id: $data['product_category_id'] ?? null,
            notes: $data['notes'] ?? null,
            completed_by: $data['completed_by'] ?? null,
            completed_at: $data['completed_at'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->stocktake_number !== null) {
            $data['stocktake_number'] = $this->stocktake_number;
        }
        if ($this->warehouse_id !== null) {
            $data['warehouse_id'] = $this->warehouse_id;
        }
        if ($this->stocktake_date !== null) {
            $data['stocktake_date'] = $this->stocktake_date;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->product_category_id !== null) {
            $data['product_category_id'] = $this->product_category_id;
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }
        if ($this->completed_by !== null) {
            $data['completed_by'] = $this->completed_by;
        }
        if ($this->completed_at !== null) {
            $data['completed_at'] = $this->completed_at;
        }

        return $data;
    }
}

