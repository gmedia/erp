<?php

namespace Database\Factories;

use App\Models\CustomerCategory;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $effectiveFrom = fake()->optional(0.7)->dateTimeBetween('-6 months', 'now');

        return [
            'product_id' => Product::factory(),
            'customer_category_id' => CustomerCategory::factory(),
            'price' => fake()->randomFloat(2, 50, 5000),
            'effective_from' => $effectiveFrom,
            'effective_until' => $effectiveFrom ? fake()->optional(0.5)->dateTimeBetween($effectiveFrom, '+1 year') : null,
        ];
    }
}
