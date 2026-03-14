<?php

namespace Database\Factories;

use App\Models\InventoryStocktake;
use App\Models\InventoryStocktakeItem;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryStocktakeItemFactory extends Factory
{
    protected $model = InventoryStocktakeItem::class;

    public function definition(): array
    {
        $systemQuantity = $this->faker->randomFloat(2, 0, 1000);
        $countedQuantity = $this->faker->boolean(70) ? $this->faker->randomFloat(2, 0, 1000) : null;
        $result = 'uncounted';

        if ($countedQuantity !== null) {
            $variance = $countedQuantity - $systemQuantity;

            if ($variance === 0.0) {
                $result = 'match';
            } elseif ($variance > 0.0) {
                $result = 'surplus';
            } else {
                $result = 'deficit';
            }
        }

        return [
            'inventory_stocktake_id' => InventoryStocktake::factory(),
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'system_quantity' => $systemQuantity,
            'counted_quantity' => $countedQuantity,
            'variance' => $countedQuantity === null ? null : $countedQuantity - $systemQuantity,
            'result' => $result,
            'notes' => $this->faker->optional()->sentence(),
            'counted_by' => $countedQuantity === null ? null : User::factory(),
            'counted_at' => $countedQuantity === null ? null : now(),
        ];
    }
}
