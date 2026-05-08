<?php

namespace Database\Factories;

use App\Models\ApPayment;
use App\Models\ApPaymentAllocation;
use App\Models\SupplierBill;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApPaymentAllocationFactory extends Factory
{
    protected $model = ApPaymentAllocation::class;

    public function definition(): array
    {
        return [
            'ap_payment_id' => ApPayment::factory(),
            'supplier_bill_id' => SupplierBill::factory(),
            'allocated_amount' => $this->faker->randomFloat(2, 100000, 10000000),
            'discount_taken' => 0,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
