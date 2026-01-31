<?php

namespace Database\Factories;

use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoaVersionFactory extends Factory
{
    protected $model = CoaVersion::class;

    public function definition(): array
    {
        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'name' => 'COA ' . $this->faker->year() . ' v' . $this->faker->randomDigitNotNull(),
            'status' => $this->faker->randomElement(['draft', 'active', 'archived']),
        ];
    }
}
