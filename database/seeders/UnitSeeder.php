<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'symbol' => 'pcs'],
            ['name' => 'Box', 'symbol' => 'box'],
            ['name' => 'Unit', 'symbol' => 'unit'],
            ['name' => 'Kilogram', 'symbol' => 'kg'],
            ['name' => 'Gram', 'symbol' => 'g'],
            ['name' => 'Liter', 'symbol' => 'L'],
            ['name' => 'Milliliter', 'symbol' => 'mL'],
            ['name' => 'Meter', 'symbol' => 'm'],
            ['name' => 'Centimeter', 'symbol' => 'cm'],
            ['name' => 'Square Meter', 'symbol' => 'm²'],
            ['name' => 'Cubic Meter', 'symbol' => 'm³'],
            ['name' => 'Hour', 'symbol' => 'hr'],
            ['name' => 'Day', 'symbol' => 'day'],
            ['name' => 'Week', 'symbol' => 'week'],
            ['name' => 'Month', 'symbol' => 'month'],
            ['name' => 'Year', 'symbol' => 'year'],
            ['name' => 'Service', 'symbol' => 'svc'],
            ['name' => 'Set', 'symbol' => 'set'],
            ['name' => 'Pair', 'symbol' => 'pair'],
            ['name' => 'Sheet', 'symbol' => 'sheet'],
        ];

        foreach ($units as $unit) {
            DB::table('units')->insert([
                'name' => $unit['name'],
                'symbol' => $unit['symbol'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
