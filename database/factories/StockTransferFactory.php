<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\StockTransfer;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StockTransfer>
 */
class StockTransferFactory extends Factory
{
    protected $model = StockTransfer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transfer_number' => null,
            'from_warehouse_id' => Warehouse::factory(),
            'to_warehouse_id' => Warehouse::factory(),
            'transfer_date' => $this->faker->date(),
            'expected_arrival_date' => $this->faker->optional()->date(),
            'status' => $this->faker->randomElement(['draft', 'pending_approval', 'approved', 'in_transit', 'received', 'cancelled']),
            'notes' => $this->faker->optional()->sentence(),
            'requested_by' => Employee::factory(),
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'shipped_by' => User::factory(),
            'shipped_at' => now(),
            'received_by' => User::factory(),
            'received_at' => now(),
            'created_by' => User::factory(),
        ];
    }
}
