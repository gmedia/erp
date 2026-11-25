<?php

namespace Database\Seeders;

use App\Models\CustomerCategory;
use Illuminate\Database\Seeder;

class CustomerCategorySeeder extends Seeder
{
    public function run(): void
    {
        // Nama Inggris lama dari seed sebelumnya di-rename (bukan dihapus) agar relasi pelanggan utuh.
        $legacyNames = [
            'Retail' => 'Eceran',
            'Wholesale' => 'Grosir',
            'Government' => 'Pemerintah',
            'Corporate' => 'Korporasi',
            'Reseller' => 'Reseller',
            'VIP' => 'VIP',
            'Online' => 'Daring',
            'Walk-in' => 'Datang Langsung',
        ];

        foreach ($legacyNames as $legacy => $replacement) {
            if (! CustomerCategory::query()->where('name', $replacement)->exists()) {
                CustomerCategory::query()->where('name', $legacy)->update(['name' => $replacement]);
            }
        }

        $categories = array_values($legacyNames);

        foreach ($categories as $name) {
            CustomerCategory::updateOrCreate(['name' => $name]);
        }
    }
}
