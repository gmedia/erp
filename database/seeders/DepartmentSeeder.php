<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Nama Inggris lama di-rename (bukan dihapus) agar relasi karyawan tetap utuh.
        $legacyNames = [
            'HR' => DemoMasterData::DEPARTMENT_HR,
            'Engineering' => DemoMasterData::DEPARTMENT_ENGINEERING,
            'Sales' => 'Penjualan',
            'Marketing' => 'Pemasaran',
            'Finance' => DemoMasterData::DEPARTMENT_FINANCE,
            'Operations' => DemoMasterData::DEPARTMENT_OPERATIONS,
            'Customer Support' => 'Layanan Pelanggan',
            'Product' => 'Produk',
            'Design' => 'Desain',
            'Legal' => 'Hukum',
        ];

        foreach ($legacyNames as $legacy => $replacement) {
            if (! Department::query()->where('name', $replacement)->exists()) {
                Department::query()->where('name', $legacy)->update(['name' => $replacement]);
            }
        }

        foreach (array_values($legacyNames) as $name) {
            Department::updateOrCreate(['name' => $name]);
        }
    }
}
