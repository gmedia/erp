<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseRequestItemFactory extends Factory
{
    protected $model = PurchaseRequestItem::class;

    public function definition(): array
    {
        $quantity = $this->faker->randomFloat(2, 1, 100);
        $unitPrice = $this->faker->optional()->randomFloat(2, 1000, 100000);

        return [
            'purchase_request_id' => PurchaseRequest::factory(),
            'product_id' => Product::factory(),
            'unit_id' => Unit::factory(),
            'quantity' => $quantity,
            'quantity_ordered' => 0,
            'estimated_unit_price' => $unitPrice,
            'estimated_total' => $unitPrice !== null ? $quantity * $unitPrice : null,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
