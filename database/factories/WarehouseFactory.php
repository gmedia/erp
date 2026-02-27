<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Warehouse>
 */
class WarehouseFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'code' => $this->faker->unique()->bothify('WH-###'),
            'name' => $this->faker->company() . ' Warehouse',
        ];
    }
}
