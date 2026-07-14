<?php

namespace Database\Factories;

use App\Models\Employee;
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
            'user_id' => User::factory(),
        ];
    }
}
