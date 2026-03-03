<?php

namespace Database\Factories;

use App\Models\InventoryStocktake;
use App\Models\JournalEntry;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockAdjustmentFactory extends Factory
{
    protected $model = StockAdjustment::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement(['draft', 'pending_approval', 'approved', 'cancelled']);

        return [
            'adjustment_number' => null,
            'warehouse_id' => Warehouse::factory(),
            'adjustment_date' => $this->faker->date(),
            'adjustment_type' => $this->faker->randomElement(['damage', 'expired', 'shrinkage', 'correction', 'stocktake_result', 'initial_stock', 'other']),
            'status' => $status,
            'inventory_stocktake_id' => $this->faker->boolean(20) ? InventoryStocktake::factory() : null,
            'notes' => $this->faker->optional()->sentence(),
            'journal_entry_id' => $this->faker->boolean(10) ? JournalEntry::factory() : null,
            'approved_by' => $status === 'approved' ? User::factory() : null,
            'approved_at' => $status === 'approved' ? now() : null,
            'created_by' => User::factory(),
        ];
    }
}
