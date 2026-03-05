<?php

namespace Database\Factories;

use App\Models\InventoryStocktake;
use App\Models\ProductCategory;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryStocktakeFactory extends Factory
{
    protected $model = InventoryStocktake::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['draft', 'in_progress', 'completed', 'cancelled']);

        return [
            'stocktake_number' => null,
            'warehouse_id' => Warehouse::factory(),
            'stocktake_date' => $this->faker->date(),
            'status' => $status,
            'product_category_id' => $this->faker->boolean(30) ? ProductCategory::factory() : null,
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
            'completed_by' => $status === 'completed' ? User::factory() : null,
            'completed_at' => $status === 'completed' ? now() : null,
        ];
    }
}

