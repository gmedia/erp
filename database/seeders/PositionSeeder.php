<?php

namespace Database\Seeders;

use App\Models\Position;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        // Hanya jabatan, bukan tingkat grade (Junior/Mid-level/Senior). W7-15.
        // Nama Inggris lama di-rename (bukan dihapus) agar relasi karyawan tetap utuh.
        $legacyNames = [
            'Staff' => 'Staf',
            'Supervisor' => 'Supervisor',
            'Driver' => DemoMasterData::POSITION_DRIVER,
            'Senior Developer' => DemoMasterData::POSITION_SENIOR_DEVELOPER,
            'Lead' => DemoMasterData::POSITION_LEAD,
            'Manager' => DemoMasterData::POSITION_MANAGER,
            'Director' => DemoMasterData::POSITION_DIRECTOR,
            'VP' => 'Wakil Presiden',
            'C-level' => 'Eksekutif Puncak',
        ];

        foreach ($legacyNames as $legacy => $replacement) {
            if (! Position::query()->where('name', $replacement)->exists()) {
                Position::query()->where('name', $legacy)->update(['name' => $replacement]);
            }
        }

        foreach (array_values($legacyNames) as $name) {
            Position::updateOrCreate(['name' => $name]);
        }

        // Bersihkan grade lama dari seed sebelumnya, hanya bila tidak dipakai karyawan
        // mana pun (data pengguna tidak pernah dihapus).
        Position::query()
            ->whereIn('name', ['Junior', 'Mid-level', 'Senior'])
            ->whereNotIn('id', DB::table('employments')->whereNotNull('position_id')->select('position_id'))
            ->delete();
    }
}
