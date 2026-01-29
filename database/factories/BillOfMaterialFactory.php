<?php

namespace Database\Factories;

use App\Models\BillOfMaterial;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillOfMaterial>
 */
class BillOfMaterialFactory extends Factory
{
    protected $model = BillOfMaterial::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'finished_product_id' => Product::factory()->finishedGood()->manufactured(),
            'raw_material_id' => Product::factory()->rawMaterial(),
            'quantity_required' => fake()->randomFloat(4, 0.1, 100),
            'unit_id' => Unit::factory(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
