<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierReturnFactory extends Factory
{
    protected $model = SupplierReturn::class;

    public function definition(): array
    {
        return [
            'return_number' => null,
            'purchase_order_id' => PurchaseOrder::factory(),
            'goods_receipt_id' => null,
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'return_date' => $this->faker->date(),
            'reason' => $this->faker->randomElement(['defective', 'wrong_item', 'excess_quantity', 'damaged', 'other']),
            'status' => $this->faker->randomElement(['draft', 'confirmed', 'cancelled']),
            'notes' => $this->faker->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    public function withGoodsReceipt(): self
    {
        return $this->state(fn () => [
            'goods_receipt_id' => GoodsReceipt::factory(),
        ]);
    }
}
