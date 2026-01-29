<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductStock>
 */
class ProductStockFactory extends Factory
{
    protected $model = ProductStock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantityOnHand = fake()->numberBetween(0, 1000);
        $quantityReserved = fake()->numberBetween(0, min($quantityOnHand, 100));

        return [
            'product_id' => Product::factory(),
            'branch_id' => Branch::factory(),
            'quantity_on_hand' => $quantityOnHand,
            'quantity_reserved' => $quantityReserved,
            'minimum_quantity' => fake()->numberBetween(10, 50),
            'average_cost' => fake()->randomFloat(2, 10, 500),
        ];
    }

    /**
     * Indicate that the stock is low.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity_on_hand' => fake()->numberBetween(0, 20),
            'minimum_quantity' => 50,
        ]);
    }
}
