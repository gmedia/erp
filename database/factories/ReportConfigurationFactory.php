<?php

namespace Database\Factories;

use App\Models\ReportConfiguration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportConfiguration>
 */
class ReportConfigurationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(ReportConfiguration::TYPES);

        return [
            'code' => fn () => str_replace('_', '-', $type) . '-' . uniqid() . '-' . random_int(0, 9999),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'report_type' => $type,
            'layout_config' => null,
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function ofType(string $type): static
    {
        return $this->state(['report_type' => $type]);
    }
}
