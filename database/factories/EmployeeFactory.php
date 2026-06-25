<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Employee::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => fn () => 'EMP-' . uniqid() . '-' . random_int(0, 9999),
            'name' => $this->faker->name(),
            'email' => fn () => 'test-' . uniqid() . '-employee@example.com',
            'phone' => $this->faker->optional()->phoneNumber(),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'branch_id' => Branch::factory(),
            'user_id' => User::factory(),
            // nullable two-decimal salary
            'salary' => $this->faker->optional(0.8, null)->randomFloat(2, 30000, 150000),
            // Random hire date within the last 10 years
            'hire_date' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'employment_status' => $this->faker->randomElement(['regular', 'intern']),
            'termination_date' => $this->faker->boolean(20)
                ? $this->faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d')
                : null,
        ];
    }
}
