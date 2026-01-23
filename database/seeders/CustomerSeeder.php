<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $branches = Branch::all();

        if ($branches->isEmpty()) {
            $this->command->warn('No branches found. Please run BranchSeeder first.');

            return;
        }

        // Create individual customers
        foreach ($branches as $branch) {
            Customer::factory()
                ->count(5)
                ->active()
                ->create(['branch_id' => $branch->id]);

            Customer::factory()
                ->count(2)
                ->company()
                ->active()
                ->create(['branch_id' => $branch->id]);

            Customer::factory()
                ->count(1)
                ->inactive()
                ->create(['branch_id' => $branch->id]);
        }

        $this->command->info('Created customers for '.count($branches).' branches.');
    }
}
