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
        // Nama Inggris lama di-rename (bukan dihapus). Simbol tetap: kode teknis.
        $legacyNames = [
            'Piece' => 'Buah',
            'Box' => DemoMasterData::UNIT_BOX,
            'Unit' => 'Unit',
            'Kilogram' => 'Kilogram',
            'Gram' => 'Gram',
            'Liter' => 'Liter',
            'Milliliter' => 'Mililiter',
            'Meter' => 'Meter',
            'Centimeter' => 'Sentimeter',
            'Square Meter' => 'Meter Persegi',
            'Cubic Meter' => 'Meter Kubik',
            'Hour' => 'Jam',
            'Day' => 'Hari',
            'Week' => 'Minggu',
            'Month' => 'Bulan',
            'Year' => 'Tahun',
            'Service' => DemoMasterData::UNIT_SERVICE,
            'Set' => DemoMasterData::UNIT_SET,
            'Pair' => 'Pasang',
            'Sheet' => DemoMasterData::UNIT_SHEET,
        ];

        foreach ($legacyNames as $legacy => $replacement) {
            if (! DB::table('units')->where('name', $replacement)->exists()) {
                DB::table('units')->where('name', $legacy)->update(['name' => $replacement]);
            }
        }

        $symbols = [
            'Buah' => 'pcs', DemoMasterData::UNIT_BOX => 'box', 'Unit' => 'unit',
            'Kilogram' => 'kg', 'Gram' => 'g', 'Liter' => 'L', 'Mililiter' => 'mL',
            'Meter' => 'm', 'Sentimeter' => 'cm', 'Meter Persegi' => 'm²', 'Meter Kubik' => 'm³',
            'Jam' => 'jam', 'Hari' => 'hari', 'Minggu' => 'minggu', 'Bulan' => 'bulan', 'Tahun' => 'tahun',
            DemoMasterData::UNIT_SERVICE => 'svc', DemoMasterData::UNIT_SET => 'set',
            'Pasang' => 'pasang', DemoMasterData::UNIT_SHEET => 'lbr',
        ];

        $units = [];
        foreach ($symbols as $name => $symbol) {
            $units[] = ['name' => $name, 'symbol' => $symbol];
        }

        foreach ($units as $unit) {
            // Idempoten: seed ulang tidak boleh melanggar unique(name).
            DB::table('units')->updateOrInsert(
                ['name' => $unit['name']],
                fn (bool $exists) => [
                    'symbol' => $unit['symbol'],
                    // created_at hanya saat insert, agar seed ulang tidak mengubah tanggal dibuat.
                    ...($exists ? [] : ['created_at' => now()]),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
