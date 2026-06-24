<?php

namespace Database\Factories;

use App\Models\AssetStocktake;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetStocktakeFactory extends Factory
{
    protected $model = AssetStocktake::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'reference' => 'ST-' . now()->getTimestampMs() . '-' . random_int(0, 9999),
            'planned_at' => $this->faker->dateTimeBetween('-6 months', '+1 month'),
            'performed_at' => $this->faker->boolean(40) ? $this->faker->dateTimeBetween('-6 months', 'now') : null,
            'status' => $this->faker->randomElement(['draft', 'in_progress', 'completed', 'cancelled']),
            'created_by' => $this->faker->boolean(70) ? User::factory() : null,
        ];
    }
}
