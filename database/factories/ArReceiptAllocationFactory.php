<?php

namespace Database\Factories;

use App\Models\ArReceipt;
use App\Models\ArReceiptAllocation;
use App\Models\CustomerInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class ArReceiptAllocationFactory extends Factory
{
    protected $model = ArReceiptAllocation::class;

    public function definition(): array
    {
        $allocatedAmount = $this->faker->randomFloat(2, 100000, 5000000);

        // Diskon alokasi (#437) harus eksplisit: default 0 agar test lain tidak memicu akun diskon.
        return [
            'ar_receipt_id' => ArReceipt::factory(),
            'customer_invoice_id' => CustomerInvoice::factory(),
            'allocated_amount' => $allocatedAmount,
            'discount_given' => 0,
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
