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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'required_product_id' => Product::factory(),
            'dependency_type' => fake()->randomElement(['prerequisite', 'recommended', 'add_on', 'alternative']),
            'minimum_quantity' => fake()->numberBetween(1, 5),
            'description' => fake()->optional()->sentence(),
            'is_active' => fake()->boolean(90),
        ];
    }

    /**
     * Indicate that this is a prerequisite dependency.
     */
    public function prerequisite(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_type' => 'prerequisite',
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that this is a recommended dependency.
     */
    public function recommended(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_type' => 'recommended',
        ]);
    }

    /**
     * Indicate that this is an add-on dependency.
     */
    public function addOn(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_type' => 'add_on',
        ]);
    }

    /**
     * Indicate that this is an alternative dependency.
     */
    public function alternative(): static
    {
        return $this->state(fn (array $attributes) => [
            'dependency_type' => 'alternative',
        ]);
    }
}
