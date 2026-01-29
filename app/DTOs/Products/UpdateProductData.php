<?php

namespace App\DTOs\Products;

readonly class UpdateProductData
{
    public function __construct(
        public ?string $code = null,
        public ?string $name = null,
        public ?string $description = null,
        public ?string $type = null,
        public ?int $category_id = null,
        public ?int $unit_id = null,
        public ?int $branch_id = null,
        public ?string $cost = null,
        public ?string $selling_price = null,
        public ?string $markup_percentage = null,
        public ?string $billing_model = null,
        public ?bool $is_recurring = null,
        public ?int $trial_period_days = null,
        public ?bool $allow_one_time_purchase = null,
        public ?bool $is_manufactured = null,
        public ?bool $is_purchasable = null,
        public ?bool $is_sellable = null,
        public ?bool $is_taxable = null,
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
            category_id: isset($data['category_id']) ? (int) $data['category_id'] : null,
            unit_id: isset($data['unit_id']) ? (int) $data['unit_id'] : null,
            branch_id: isset($data['branch_id']) ? (int) $data['branch_id'] : null,
            cost: $data['cost'] ?? null,
            selling_price: $data['selling_price'] ?? null,
            markup_percentage: $data['markup_percentage'] ?? null,
            billing_model: $data['billing_model'] ?? null,
            is_recurring: isset($data['is_recurring']) ? (bool) $data['is_recurring'] : null,
            trial_period_days: isset($data['trial_period_days']) ? (int) $data['trial_period_days'] : null,
            allow_one_time_purchase: isset($data['allow_one_time_purchase']) ? (bool) $data['allow_one_time_purchase'] : null,
            is_manufactured: isset($data['is_manufactured']) ? (bool) $data['is_manufactured'] : null,
            is_purchasable: isset($data['is_purchasable']) ? (bool) $data['is_purchasable'] : null,
            is_sellable: isset($data['is_sellable']) ? (bool) $data['is_sellable'] : null,
            is_taxable: isset($data['is_taxable']) ? (bool) $data['is_taxable'] : null,
            status: $data['status'] ?? null,
            notes: $data['notes'] ?? null,
        );
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->code !== null) $data['code'] = $this->code;
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->description !== null) $data['description'] = $this->description;
        if ($this->type !== null) $data['type'] = $this->type;
        if ($this->category_id !== null) $data['category_id'] = $this->category_id;
        if ($this->unit_id !== null) $data['unit_id'] = $this->unit_id;
        if ($this->branch_id !== null) $data['branch_id'] = $this->branch_id;
        if ($this->cost !== null) $data['cost'] = $this->cost;
        if ($this->selling_price !== null) $data['selling_price'] = $this->selling_price;
        if ($this->markup_percentage !== null) $data['markup_percentage'] = $this->markup_percentage;
        if ($this->billing_model !== null) $data['billing_model'] = $this->billing_model;
        if ($this->is_recurring !== null) $data['is_recurring'] = $this->is_recurring;
        if ($this->trial_period_days !== null) $data['trial_period_days'] = $this->trial_period_days;
        if ($this->allow_one_time_purchase !== null) $data['allow_one_time_purchase'] = $this->allow_one_time_purchase;
        if ($this->is_manufactured !== null) $data['is_manufactured'] = $this->is_manufactured;
        if ($this->is_purchasable !== null) $data['is_purchasable'] = $this->is_purchasable;
        if ($this->is_sellable !== null) $data['is_sellable'] = $this->is_sellable;
        if ($this->is_taxable !== null) $data['is_taxable'] = $this->is_taxable;
        if ($this->status !== null) $data['status'] = $this->status;
        if ($this->notes !== null) $data['notes'] = $this->notes;

        return $data;
    }
}
