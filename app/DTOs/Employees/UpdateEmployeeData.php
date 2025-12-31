<?php

namespace App\DTOs\Employees;

readonly class UpdateEmployeeData
{
    public function __construct(
        public ?string $name = null,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $department = null,
        public ?string $position = null,
        public ?string $salary = null,
        public ?string $hire_date = null,
    ) {}

    /**
     * Create DTO from request array.
     *
     * @param  array{name?: string, email?: string, phone?: string|null, department?: string, position?: string, salary?: string, hire_date?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            email: $data['email'] ?? null,
            phone: $data['phone'] ?? null,
            department: $data['department'] ?? null,
            position: $data['position'] ?? null,
            salary: $data['salary'] ?? null,
            hire_date: $data['hire_date'] ?? null,
        );
    }

    /**
     * Convert DTO to array for model update, filtering out null values.
     *
     * @return array{name?: string, email?: string, phone?: string|null, department?: string, position?: string, salary?: string, hire_date?: string}
     */
    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }
        if ($this->email !== null) {
            $data['email'] = $this->email;
        }
        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }
        if ($this->department !== null) {
            $data['department'] = $this->department;
        }
        if ($this->position !== null) {
            $data['position'] = $this->position;
        }
        if ($this->salary !== null) {
            $data['salary'] = $this->salary;
        }
        if ($this->hire_date !== null) {
            $data['hire_date'] = $this->hire_date;
        }

        return $data;
    }
}
