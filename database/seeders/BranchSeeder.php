<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branches = [
            'Head Office', 'Branch 1', 'Branch 2', 'Branch 3'
        ];

        foreach ($branches as $name) {
            Branch::updateOrCreate(['name' => $name]);
        }
    }
}
