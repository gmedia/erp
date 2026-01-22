<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            'Junior', 'Mid-level', 'Senior', 'Senior Developer',
            'Lead', 'Manager', 'Director', 'VP', 'C-level'
        ];

        foreach ($positions as $name) {
            Position::updateOrCreate(['name' => $name]);
        }
    }
}
