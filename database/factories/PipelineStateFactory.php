<?php

namespace Database\Factories;

use App\Models\Pipeline;
use App\Models\PipelineState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PipelineState>
 */
class PipelineStateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pipeline_id' => Pipeline::factory(),
            'code' => $this->faker->unique()->slug,
            'name' => $this->faker->word,
            'type' => $this->faker->randomElement(['initial', 'intermediate', 'final']),
            'color' => $this->faker->hexColor,
            'icon' => $this->faker->word,
            'description' => $this->faker->sentence,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'metadata' => ['key' => 'value'],
        ];
    }
}
