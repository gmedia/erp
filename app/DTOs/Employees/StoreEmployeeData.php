<?php

namespace App\DTOs\Employees;

readonly class StoreEmployeeData
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $department,
        public string $position,
        public string $salary,
        public string $hire_date,
    ) {}

    /**
     * Create DTO from request array.
     *
     * @param  array{name: string, email: string, phone: string|null, department: string, position: string, salary: string, hire_date: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'],
            department: $data['department'],
            position: $data['position'],
            salary: $data['salary'],
            hire_date: $data['hire_date'],
        );
    }

    /**
     * Convert DTO to array for model creation.
     *
     * @return array{name: string, email: string, phone: string|null, department: string, position: string, salary: string, hire_date: string}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'department' => $this->department,
            'position' => $this->position,
            'salary' => $this->salary,
            'hire_date' => $this->hire_date,
        ];
    }
}
