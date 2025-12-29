<?php

namespace Database\Factories;

use App\Models\Employee;
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
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'department' => $this->faker->randomElement(['Engineering', 'Marketing', 'Sales', 'HR', 'Finance']),
            'position' => $this->faker->jobTitle(),
            // Two‑decimal salary between 30 000 and 150 000
            'salary' => $this->faker->randomFloat(2, 30000, 150000),
            // Random hire date within the last 10 years
            'hire_date' => $this->faker->dateTimeBetween('-10 years', 'now')->format('Y-m-d'),
        ];
    }
}
