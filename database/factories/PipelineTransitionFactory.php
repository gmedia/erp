<?php

namespace Database\Factories;

use App\Models\Pipeline;
use App\Models\PipelineState;
use App\Models\PipelineTransition;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineTransitionFactory extends Factory
{
    protected $model = PipelineTransition::class;

    public function definition(): array
    {
        return [
            'pipeline_id' => Pipeline::factory(),
            'from_state_id' => PipelineState::factory(),
            'to_state_id' => PipelineState::factory(),
            'name' => $this->faker->words(2, true),
            'code' => $this->faker->unique()->slug(),
            'description' => $this->faker->sentence(),
            'required_permission' => null,
            'guard_conditions' => [],
            'requires_confirmation' => false,
            'requires_comment' => false,
            'requires_approval' => false,
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
        ];
    }
}
