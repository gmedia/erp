<?php

namespace Database\Factories;

use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fiscal_year_id' => FiscalYear::factory(),
            'entry_number' => 'JV-' . $this->faker->unique()->numerify('#####'),
            'entry_date' => $this->faker->date(),
            'reference' => $this->faker->word,
            'description' => $this->faker->sentence,
            'status' => 'draft',
            'created_by' => User::factory(),
        ];
    }
}
