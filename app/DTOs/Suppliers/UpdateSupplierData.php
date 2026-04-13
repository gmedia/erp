<?php

namespace App\DTOs\Suppliers;

use App\DTOs\Concerns\FiltersNullUpdateData;

readonly class UpdateSupplierData
{
    use FiltersNullUpdateData;

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
     * @param  array{
     *     name?: string,
     *     email?: string,
     *     phone?: string,
     *     address?: string,
     *     branch_id?: int,
     *     category?: string,
     *     status?: string
     * }  $data
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
     */
    public function toArray(): array
    {
        return $this->filterNullUpdateData([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'branch_id' => $this->branch_id,
            'category' => $this->category,
            'status' => $this->status,
        ]);
    }
}
