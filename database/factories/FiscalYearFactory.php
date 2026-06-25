<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FiscalYear>
 */
class FiscalYearFactory extends Factory
{
    protected $model = FiscalYear::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $year = 2020 + random_int(0, 50);

        return [
            'name' => fn () => 'FY-' . $year . '-' . uniqid() . '-' . random_int(0, 9999),
            'start_date' => $year . '-01-01',
            'end_date' => $year . '-12-31',
            'status' => 'open',
        ];
    }
}
