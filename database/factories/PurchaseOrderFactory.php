<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            'draft',
            'pending_approval',
            'confirmed',
            'rejected',
            'partially_received',
            'fully_received',
            'cancelled',
            'closed',
        ]);

        return [
            'po_number' => null,
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'order_date' => $this->faker->date(),
            'expected_delivery_date' => $this->faker->optional()->date(),
            'payment_terms' => $this->faker->optional()->randomElement(['Net 7', 'Net 14', 'Net 30']),
            'currency' => 'IDR',
            'subtotal' => $this->faker->randomFloat(2, 100000, 10000000),
            'tax_amount' => $this->faker->randomFloat(2, 0, 1000000),
            'discount_amount' => $this->faker->randomFloat(2, 0, 500000),
            'grand_total' => $this->faker->randomFloat(2, 100000, 10000000),
            'status' => $status,
            'notes' => $this->faker->optional()->sentence(),
            'shipping_address' => $this->faker->optional()->address(),
            'approved_by' => in_array($status, [
                'confirmed',
                'partially_received',
                'fully_received',
                'closed',
            ], true) ? User::factory() : null,
            'approved_at' => in_array($status, [
                'confirmed',
                'partially_received',
                'fully_received',
                'closed',
            ], true) ? now() : null,
            'created_by' => User::factory(),
        ];
    }
}
