<?php

namespace Database\Seeders;

use App\Models\ApprovalDelegation;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApprovalDelegationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::pluck('id')->toArray();

        // If we don't have enough users, create some
        if (count($users) < 5) {
            $users = User::factory()->count(10)->create()->pluck('id')->toArray();
        }

        // Create 20 random delegations
        for ($i = 0; $i < 20; $i++) {
            $delegatorId = strval($this->getRandomElement($users));
            $delegateId = strval($this->getRandomElement($users, [$delegatorId]));

            ApprovalDelegation::factory()->create([
                'delegator_user_id' => $delegatorId,
                'delegate_user_id' => $delegateId,
            ]);
        }
    }

    /**
     * Get a random element from an array, excluding specific values.
     */
    private function getRandomElement(array $array, array $exclude = [])
    {
        $filtered = array_diff($array, $exclude);
        if (empty($filtered)) {
            return $array[array_rand($array)];
        }
        return $filtered[array_rand($filtered)];
    }
}
