<?php

namespace App\DTOs\Products;

readonly class UpdateProductData
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $type = null,
        public ?int $product_category_id = null,
        public ?int $unit_id = null,
        public ?int $branch_id = null,
        public ?string $cost = null,
        public ?string $selling_price = null,
        public ?string $billing_model = null,
        public ?string $status = null,
        public ?string $notes = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'] ?? null,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            type: $data['type'] ?? null,
            product_category_id: isset($data['product_category_id']) ? (int) $data['product_category_id'] : null,
            unit_id: isset($data['unit_id']) ? (int) $data['unit_id'] : null,
            branch_id: isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            cost: $data['cost'] ?? null,
            selling_price: $data['selling_price'] ?? null,
            billing_model: $data['billing_model'] ?? null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->code !== null) {
            $data['code'] = $this->code;
        }
        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->description !== null) {
            $data['description'] = $this->description;
        }
        if ($this->type !== null) {
            $data['type'] = $this->type;
        }
        if ($this->product_category_id !== null) {
            $data['product_category_id'] = $this->product_category_id;
        }
        if ($this->unit_id !== null) {
            $data['unit_id'] = $this->unit_id;
        }
        if ($this->branch_id !== null) {
            $data['branch_id'] = $this->branch_id;
        }
        if ($this->cost !== null) {
            $data['cost'] = $this->cost;
        }
        if ($this->selling_price !== null) {
            $data['selling_price'] = $this->selling_price;
        }
        if ($this->billing_model !== null) {
            $data['billing_model'] = $this->billing_model;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->notes !== null) {
            $data['notes'] = $this->notes;
        }

        return $data;
    }
}
