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
        $positions = [
            'Staff', 'Supervisor', 'Driver', 'Senior Developer',
            'Lead', 'Manager', 'Director', 'VP', 'C-level',
        ];

        foreach ($positions as $name) {
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
