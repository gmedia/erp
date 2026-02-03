<?php

namespace Database\Factories;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetLocation;
use App\Models\AssetModel;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $purchaseDate = $this->faker->dateTimeBetween('-5 years', 'now');
        $usefulLifeMonths = $this->faker->randomElement([24, 36, 48, 60, 120]);
        $purchaseCost = $this->faker->numberBetween(2_000_000, 150_000_000);
        $salvageValue = (int) round($purchaseCost * $this->faker->randomFloat(2, 0, 0.1));
        $accumulated = 0;

        return [
            'asset_code' => $this->faker->unique()->bothify('FA-######'),
            'name' => $this->faker->words(3, true),
            'asset_model_id' => $this->faker->boolean(60) ? AssetModel::factory() : null,
            'asset_category_id' => AssetCategory::factory(),
            'serial_number' => $this->faker->optional()->bothify('SN-########'),
            'barcode' => $this->faker->optional()->unique()->bothify('BC-########'),
            'branch_id' => Branch::factory(),
            'asset_location_id' => $this->faker->boolean(70) ? AssetLocation::factory() : null,
            'department_id' => $this->faker->boolean(40) ? Department::factory() : null,
            'employee_id' => $this->faker->boolean(40) ? Employee::factory() : null,
            'supplier_id' => $this->faker->boolean(60) ? Supplier::factory() : null,
            'purchase_date' => $purchaseDate->format('Y-m-d'),
            'purchase_cost' => $purchaseCost,
            'currency' => 'IDR',
            'warranty_end_date' => $this->faker->boolean(50) ? $this->faker->dateTimeBetween($purchaseDate, '+2 years')->format('Y-m-d') : null,
            'status' => $this->faker->randomElement(['draft', 'active', 'maintenance']),
            'condition' => $this->faker->boolean(70) ? $this->faker->randomElement(['good', 'needs_repair', 'damaged']) : null,
            'notes' => $this->faker->optional()->sentence(),
            'depreciation_method' => 'straight_line',
            'depreciation_start_date' => $this->faker->boolean(80) ? $purchaseDate->format('Y-m-d') : null,
            'useful_life_months' => $this->faker->boolean(80) ? $usefulLifeMonths : null,
            'salvage_value' => $salvageValue,
            'accumulated_depreciation' => $accumulated,
            'book_value' => $purchaseCost - $accumulated,
            'depreciation_expense_account_id' => null,
            'accumulated_depr_account_id' => null,
        ];
    }
}
