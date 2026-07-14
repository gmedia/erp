<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employment>
 */
class EmploymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Employment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => fn () => Company::factory()->create()->id,
            'branch_id' => null,
            'salary' => $this->faker->randomFloat(2, 3000, 15000),
            'hire_date' => $this->faker->date(),
            'termination_date' => null,
            'employment_status' => 'active',
            'is_current' => true,
        ];
    }
}
