<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductionOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionOrder>
 */
class ProductionOrderFactory extends Factory
{
    protected $model = ProductionOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productionDate = fake()->dateTimeBetween('-2 months', '+1 month');
        $status = fake()->randomElement(['draft', 'in_progress', 'completed', 'cancelled']);
        
        $completionDate = null;
        if ($status === 'completed') {
            $completionDate = fake()->dateTimeBetween($productionDate, 'now');
        }

        return [
            'order_number' => 'PO-' . date('Ymd') . '-' . fake()->unique()->numberBetween(1000, 9999),
            'product_id' => Product::factory()->finishedGood(),
            'branch_id' => fake()->optional()->randomElement([1, 2, 3]),
            'quantity_to_produce' => fake()->numberBetween(10, 500),
            'production_date' => $productionDate,
            'completion_date' => $completionDate,
            'status' => $status,
            'total_cost' => $status === 'completed' ? fake()->randomFloat(2, 1000, 50000) : 0,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the production order is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'production_date' => now()->subDays(fake()->numberBetween(1, 14)),
            'completion_date' => null,
        ]);
    }

    /**
     * Indicate that the production order is completed.
     */
    public function completed(): static
    {
        $productionDate = now()->subDays(fake()->numberBetween(7, 60));
        
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'production_date' => $productionDate,
            'completion_date' => fake()->dateTimeBetween($productionDate, 'now'),
            'total_cost' => fake()->randomFloat(2, 1000, 50000),
        ]);
    }
}
