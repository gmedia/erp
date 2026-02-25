<?php

namespace Database\Factories;

use App\Models\PipelineTransition;
use App\Models\PipelineTransitionAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class PipelineTransitionActionFactory extends Factory
{
    protected $model = PipelineTransitionAction::class;

    public function definition(): array
    {
        return [
            'pipeline_transition_id' => PipelineTransition::factory(),
            'action_type' => 'update_field',
            'execution_order' => $this->faker->numberBetween(1, 10),
            'config' => ['field' => 'status', 'value' => 'active'],
            'is_async' => false,
            'on_failure' => 'abort',
            'is_active' => true,
        ];
    }
}
