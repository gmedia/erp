<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductionOrder>
 */
class ProductionOrderFactory extends Factory
{
    protected $model = ProductionOrder::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plannedStart = fake()->dateTimeBetween('-2 months', '+1 month');
        $status = fake()->randomElement(['draft', 'in_progress', 'completed', 'cancelled']);

        $plannedEnd = fake()->optional(0.7)->dateTimeBetween($plannedStart, '+3 months');
        $actualStart = in_array($status, ['in_progress', 'completed'])
            ? fake()->dateTimeBetween($plannedStart, 'now')
            : null;
        $actualEnd = $status === 'completed'
            ? fake()->dateTimeBetween($actualStart ?? $plannedStart, 'now')
            : null;

        return [
            'order_number' => 'MO-' . date('Y') . '-' . fake()->unique()->numberBetween(100000, 999999),
            'product_id' => Product::factory()->finishedGood(),
            'branch_id' => fake()->optional()->randomElement([1, 2, 3]),
            'quantity' => fake()->numberBetween(10, 500),
            'unit_id' => Unit::factory(),
            'planned_start_date' => $plannedStart,
            'planned_end_date' => $plannedEnd,
            'actual_start_date' => $actualStart,
            'actual_end_date' => $actualEnd,
            'status' => $status,
            'total_cost' => $status === 'completed' ? fake()->randomFloat(2, 1000, 50000) : 0,
            'notes' => fake()->optional()->sentence(),
            'created_by' => fake()->optional(0.7)->passthrough(User::factory()),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
            'planned_start_date' => now()->subDays(fake()->numberBetween(1, 14)),
            'actual_start_date' => now()->subDays(fake()->numberBetween(1, 7)),
            'planned_end_date' => now()->addDays(fake()->numberBetween(1, 14)),
            'actual_end_date' => null,
        ]);
    }

    public function completed(): static
    {
        $plannedStart = now()->subDays(fake()->numberBetween(7, 60));
        $actualStart = fake()->dateTimeBetween($plannedStart, 'now');

        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'planned_start_date' => $plannedStart,
            'actual_start_date' => $actualStart,
            'planned_end_date' => now()->subDays(fake()->numberBetween(0, 3)),
            'actual_end_date' => fake()->dateTimeBetween($actualStart, 'now'),
            'total_cost' => fake()->randomFloat(2, 1000, 50000),
        ]);
    }
}
