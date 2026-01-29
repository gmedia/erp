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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['raw_material', 'finished_good', 'purchased_good', 'service']);
        $cost = fake()->randomFloat(2, 10, 1000);
        $markup = fake()->randomFloat(2, 20, 100);
        $sellingPrice = $cost * (1 + ($markup / 100));

        return [
            'code' => 'PRD-' . strtoupper(fake()->unique()->bothify('??###')),
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'type' => $type,
            'category_id' => ProductCategory::factory(),
            'unit_id' => Unit::factory(),
            'branch_id' => fake()->boolean(70) ? Branch::factory() : null,
            'cost' => $cost,
            'selling_price' => round($sellingPrice, 2),
            'markup_percentage' => $markup,
            'billing_model' => fake()->randomElement(['one_time', 'subscription']),
            'is_recurring' => fake()->boolean(20),
            'trial_period_days' => fake()->optional(0.3)->numberBetween(7, 30),
            'allow_one_time_purchase' => fake()->boolean(80),
            'is_manufactured' => $type === 'finished_good' ? fake()->boolean(50) : false,
            'is_purchasable' => in_array($type, ['raw_material', 'purchased_good']) ? true : fake()->boolean(80),
            'is_sellable' => in_array($type, ['finished_good', 'purchased_good', 'service']) ? true : fake()->boolean(20),
            'is_taxable' => fake()->boolean(90),
            'status' => fake()->randomElement(['active', 'inactive', 'discontinued']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the product is a raw material.
     */
    public function rawMaterial(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'raw_material',
            'is_manufactured' => false,
            'is_purchasable' => true,
            'is_sellable' => false,
            'billing_model' => 'one_time',
            'is_recurring' => false,
        ]);
    }

    /**
     * Indicate that the product is a finished good.
     */
    public function finishedGood(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'finished_good',
            'is_sellable' => true,
            'billing_model' => 'one_time',
        ]);
    }

    /**
     * Indicate that the product is manufactured.
     */
    public function manufactured(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'finished_good',
            'is_manufactured' => true,
            'is_sellable' => true,
        ]);
    }

    /**
     * Indicate that the product is a service.
     */
    public function service(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'service',
            'is_manufactured' => false,
            'is_purchasable' => false,
            'is_sellable' => true,
        ]);
    }

    /**
     * Indicate that the product is subscription-based.
     */
    public function subscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_model' => 'subscription',
            'is_recurring' => true,
            'trial_period_days' => fake()->numberBetween(7, 30),
        ]);
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }
}
