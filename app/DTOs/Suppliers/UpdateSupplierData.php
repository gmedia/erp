<?php

namespace App\DTOs\Suppliers;

readonly class UpdateSupplierData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?int $branch_id = null,
        public ?string $category = null,
        public ?string $status = null,
    ) {}

    /**
     * Create DTO from request array.
     *
     * @param  array{name?: string, email?: string, phone?: string, address?: string, branch_id?: int, category?: string, status?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            address: $data['address'] ?? null,
            branch_id: $data['branch_id'] ?? null,
            category: $data['category'] ?? null,
            status: $data['status'] ?? null,
        );
    }

    /**
     * Convert DTO to array for model update, filtering out null values.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [];
        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->email !== null) $data['email'] = $this->email;
        if ($this->phone !== null) $data['phone'] = $this->phone;
        if ($this->address !== null) $data['address'] = $this->address;
        if ($this->branch_id !== null) $data['branch_id'] = $this->branch_id;
        if ($this->category !== null) $data['category'] = $this->category;
        if ($this->status !== null) $data['status'] = $this->status;

        return $data;
    }
}
