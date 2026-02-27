<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pipeline>
 */
class PipelineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Pipeline ' . $this->faker->words(2, true),
            'code' => $this->faker->unique()->slug(),
            'entity_type' => $this->faker->randomElement(['App\Models\Asset', 'App\Models\PurchaseOrder', 'App\Models\PurchaseRequest', 'App\Models\JournalEntry']),
            'description' => $this->faker->sentence(),
            'version' => 1,
            'is_active' => $this->faker->boolean(80),
            'conditions' => null,
            'created_by' => User::factory(),
        ];
    }
}
