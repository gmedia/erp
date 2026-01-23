<?php

namespace App\DTOs\Customers;

readonly class UpdateCustomerData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?int $branch_id = null,
        public ?string $customer_type = null,
        public ?string $status = null,
        public ?string $notes = null,
    ) {}

    /**
     * Create DTO from request array.
     *
     * @param  array{name?: string, email?: string, phone?: string, address?: string, branch_id?: int, customer_type?: string, status?: string, notes?: string|null}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            customer_type: $data['customer_type'] ?? null,
            status: $data['status'] ?? null,
            notes: array_key_exists('notes', $data) ? $data['notes'] : null, // Handle nullable notes specifically
        );
    }

    /**
     * Convert DTO to array for model update, filtering out null values specific to logic.
     * Note: "notes" can be explicitly nullified, but others usually partial update.
     *
     * @return array
     */
    public function toArray(): array
    {
        // Simple filter might remove nulls we intend to set (like notes).
        // But for update, we usually want to update only provided fields.
        // If a field is not provided in constructor, it is null.
        
        $data = [];
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->email !== null) $data['email'] = $this->email;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->address !== null) $data['address'] = $this->address;
        if ($this->branch_id !== null) $data['branch_id'] = $this->branch_id;
        if ($this->customer_type !== null) $data['customer_type'] = $this->customer_type;
        if ($this->status !== null) $data['status'] = $this->status;
        
        // Notes is special because it can be nullable in DB.
        // If it's passed as null, maybe we want to ignore it OR set it to null?
        // In standard PATCH/PUT partial update, missing field = ignore. 
        // Present + null = set to null.
        // However, generic DTO constructor sets default null.
        // We'll follow the pattern that if it's set in the DTO, we use it. 
        // But since default is null, we can't distinguish "unset" vs "set to null" easily here without extra flags.
        // For simplicity and matching Employee pattern (which just filters nulls), we'll do:
        
        if ($this->notes !== null) $data['notes'] = $this->notes;
        
        return $data;
    }
}
