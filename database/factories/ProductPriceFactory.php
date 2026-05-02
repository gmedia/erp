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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $effectiveFrom = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'product_id' => Product::factory(),
            'customer_category_id' => fake()->boolean(70) ? CustomerCategory::factory() : null,
            'price' => fake()->randomFloat(2, 50, 5000),
            'effective_from' => $effectiveFrom,
            'effective_until' => fake()->optional(0.5)->dateTimeBetween($effectiveFrom, '+1 year'),
        ];
    }
}
