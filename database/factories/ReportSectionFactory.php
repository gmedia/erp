<?php

namespace Database\Factories;

use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReportSection>
 */
class ReportSectionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'report_configuration_id' => ReportConfiguration::factory(),
            'parent_id' => null,
            'code' => $this->faker->unique()->slug(2),
            'name' => $this->faker->words(3, true),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'section_type' => $this->faker->randomElement(ReportSection::SECTION_TYPES),
            'account_type_filter' => null,
            'account_sub_type_filter' => null,
            'sign_convention' => ReportSection::SIGN_NORMAL,
            'formula' => null,
            'is_active' => true,
        ];
    }

    public function header(): static
    {
        return $this->state(['section_type' => ReportSection::TYPE_HEADER]);
    }

    public function detail(string $type, ?string $subType = null): static
    {
        return $this->state([
            'section_type' => ReportSection::TYPE_DETAIL,
            'account_type_filter' => $type,
            'account_sub_type_filter' => $subType,
        ]);
    }

    public function subtotal(): static
    {
        return $this->state(['section_type' => ReportSection::TYPE_SUBTOTAL]);
    }

    public function total(): static
    {
        return $this->state(['section_type' => ReportSection::TYPE_TOTAL]);
    }

    public function reversed(): static
    {
        return $this->state(['sign_convention' => ReportSection::SIGN_REVERSED]);
    }
}
