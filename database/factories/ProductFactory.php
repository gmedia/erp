<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cost = fake()->randomFloat(2, 10, 1000);
        $sellingPrice = $cost * (1 + (fake()->randomFloat(2, 20, 100) / 100));

        return [
            'code' => 'PRD-' . strtoupper(fake()->unique()->bothify('??###')),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(['raw_material', 'finished_good', 'purchased_good', 'service']),
            'product_category_id' => ProductCategory::factory(),
            'unit_id' => Unit::factory(),
            'branch_id' => fake()->boolean(70) ? Branch::factory() : null,
            'cost' => $cost,
            'selling_price' => round($sellingPrice, 2),
            'billing_model' => fake()->randomElement(['one_time', 'subscription']),
            'status' => fake()->randomElement(['active', 'inactive', 'discontinued']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function rawMaterial(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'raw_material',
            'billing_model' => 'one_time',
        ]);
    }

    public function finishedGood(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'finished_good',
            'billing_model' => 'one_time',
        ]);
    }

    public function manufactured(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'finished_good',
        ]);
    }

    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
        ]);
    }

    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_model' => 'subscription',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
