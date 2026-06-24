<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Supplier;
use App\Models\SupplierCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'email' => 'test-' . now()->getTimestampMs() . '-supplier@example.com',
            'phone' => $this->faker->phoneNumber(),
            'address' => $this->faker->address(),
            'branch_id' => Branch::factory(),
            'category_id' => SupplierCategory::factory(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
