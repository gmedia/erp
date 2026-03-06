<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class GoodsReceiptFactory extends Factory
{
    protected $model = GoodsReceipt::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['draft', 'confirmed', 'cancelled']);

        return [
            'gr_number' => null,
            'purchase_order_id' => PurchaseOrder::factory(),
            'warehouse_id' => Warehouse::factory(),
            'receipt_date' => $this->faker->date(),
            'supplier_delivery_note' => $this->faker->optional()->bothify('SJ-####'),
            'status' => $status,
            'notes' => $this->faker->optional()->sentence(),
            'received_by' => $this->faker->optional()->boolean(70) ? Employee::factory() : null,
            'confirmed_by' => $status === 'confirmed' ? User::factory() : null,
            'confirmed_at' => $status === 'confirmed' ? now() : null,
            'created_by' => User::factory(),
        ];
    }
}
