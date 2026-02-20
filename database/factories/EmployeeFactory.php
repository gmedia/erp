<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
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
            'employee_id' => 'EMP-' . $this->faker->unique()->randomNumber(5, true),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'branch_id' => \App\Models\Branch::factory(),
            'user_id' => $this->faker->boolean(20) ? User::factory() : null,
            // nullable two-decimal salary
            'salary' => $this->faker->optional(0.8, null)->randomFloat(2, 30000, 150000),
            // Random hire date within the last 10 years
            'hire_date' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
            'employment_status' => $this->faker->randomElement(['regular', 'intern']),
            'termination_date' => $this->faker->boolean(20) ? $this->faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d') : null,
        ];
    }
}
