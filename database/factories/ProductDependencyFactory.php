<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductDependency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductDependency>
 */
class ProductDependencyFactory extends Factory
{
    protected $model = ProductDependency::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'related_product_id' => Product::factory(),
            'type' => fake()->randomElement(['prerequisite', 'recommended', 'add_on', 'alternative']),
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function prerequisite(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'prerequisite',
        ]);
    }

    public function recommended(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'recommended',
        ]);
    }

    public function addOn(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'add_on',
        ]);
    }

    public function alternative(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'alternative',
        ]);
    }
}
