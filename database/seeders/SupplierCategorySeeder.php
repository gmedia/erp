<?php

namespace Database\Seeders;

use App\Models\SupplierCategory;
use Illuminate\Database\Seeder;

class SupplierCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Nama Inggris lama dari seed sebelumnya di-rename (bukan dihapus) agar relasi supplier utuh.
        $legacyNames = [
            'Electronics' => 'Elektronik',
            'Office Supplies' => 'Perlengkapan Kantor',
            'Furniture' => 'Furnitur',
            'IT Services' => DemoMasterData::SUPPLIER_CATEGORY_IT_SERVICES,
            'Logistics' => 'Logistik',
            'Marketing' => 'Pemasaran',
            'Maintenance' => 'Pemeliharaan',
            'Utilities' => 'Utilitas',
        ];

        foreach ($legacyNames as $legacy => $replacement) {
            if (! SupplierCategory::query()->where('name', $replacement)->exists()) {
                SupplierCategory::query()->where('name', $legacy)->update(['name' => $replacement]);
            }
        }

        $categories = array_values($legacyNames);

        foreach ($categories as $name) {
            SupplierCategory::updateOrCreate(['name' => $name]);
        }
    }
}
