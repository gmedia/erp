<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\CoaVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'coa_version_id' => CoaVersion::factory(),
            'code' => $this->faker->unique()->numerify('#####'),
            'name' => $this->faker->words(3, true),
            'type' => $this->faker->randomElement(['asset', 'liability', 'equity', 'revenue', 'expense']),
            'sub_type' => null,
            'normal_balance' => $this->faker->randomElement(['debit', 'credit']),
            'level' => 1,
            'is_active' => true,
            'is_cash_flow' => false,
            'description' => $this->faker->sentence(),
        ];
    }
}
