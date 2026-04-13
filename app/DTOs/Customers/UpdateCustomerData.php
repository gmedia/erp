<?php

namespace App\DTOs\Customers;

use App\DTOs\Concerns\FiltersNullUpdateData;

readonly class UpdateCustomerData
{
    use FiltersNullUpdateData;

    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?int $branch_id = null,
        public ?int $category_id = null,
        public ?string $status = null,
        public ?string $notes = null,
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
     *     category_id?: int,
     *     status?: string,
     *     notes?: string|null
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
            category_id: $data['category_id'] ?? null,
            status: $data['status'] ?? null,
            notes: array_key_exists('notes', $data) ? $data['notes'] : null, // Handle nullable notes specifically
        );
    }

    /**
     * Convert DTO to array for model update, filtering out null values specific to logic.
     * Note: "notes" can be explicitly nullified, but others usually partial update.
     */
    public function toArray(): array
    {
        return $this->filterNullUpdateData([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'branch_id' => $this->branch_id,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'notes' => $this->notes,
        ]);
    }
}
