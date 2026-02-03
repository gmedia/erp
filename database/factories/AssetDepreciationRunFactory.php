<?php

namespace Database\Factories;

use App\Models\AssetDepreciationRun;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetDepreciationRunFactory extends Factory
{
    protected $model = AssetDepreciationRun::class;

    public function definition(): array
    {
        $periodStart = $this->faker->dateTimeBetween('-1 year', 'now');
        $periodEnd = (clone $periodStart)->modify('+1 month')->modify('-1 day');

        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'status' => $this->faker->randomElement(['draft', 'calculated', 'posted', 'void']),
            'journal_entry_id' => $this->faker->boolean(40) ? JournalEntry::factory() : null,
            'created_by' => $this->faker->boolean(70) ? User::factory() : null,
            'posted_by' => null,
            'posted_at' => null,
        ];
    }
}
