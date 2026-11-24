<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Nama Inggris lama dari seed sebelumnya di-rename (bukan dihapus) agar relasi tetap utuh.
        $legacyNames = [
            'Electronics' => DemoMasterData::PRODUCT_CATEGORY_ELECTRONICS,
            'Office Supplies' => DemoMasterData::PRODUCT_CATEGORY_OFFICE_SUPPLIES,
            'Furniture' => DemoMasterData::PRODUCT_CATEGORY_FURNITURE,
            'Raw Materials - Wood' => DemoMasterData::PRODUCT_CATEGORY_RAW_WOOD,
            'Raw Materials - Hardware' => DemoMasterData::PRODUCT_CATEGORY_RAW_HARDWARE,
            'Finished Goods - Furniture' => DemoMasterData::PRODUCT_CATEGORY_FINISHED_FURNITURE,
            'Consulting Services' => DemoMasterData::PRODUCT_CATEGORY_CONSULTING,
            'Maintenance Services' => DemoMasterData::PRODUCT_CATEGORY_MAINTENANCE,
            'Training Services' => DemoMasterData::PRODUCT_CATEGORY_TRAINING,
            'Software & SaaS' => DemoMasterData::PRODUCT_CATEGORY_SOFTWARE,
        ];

        foreach ($legacyNames as $legacy => $replacement) {
            $exists = DB::table('product_categories')->where('name', $replacement)->exists();
            if (! $exists) {
                DB::table('product_categories')->where('name', $legacy)->update(['name' => $replacement]);
            }
        }

        $categories = [
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_ELECTRONICS,
                'description' => 'Produk dan perangkat elektronik',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_OFFICE_SUPPLIES,
                'description' => 'Peralatan dan perlengkapan kantor',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_FURNITURE,
                'description' => 'Furnitur kantor dan rumah',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_RAW_WOOD,
                'description' => 'Bahan kayu dan papan',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_RAW_HARDWARE,
                'description' => 'Komponen dan pengikat perangkat keras',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_FINISHED_FURNITURE,
                'description' => 'Produk furnitur hasil produksi',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_CONSULTING,
                'description' => 'Jasa konsultasi profesional',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_MAINTENANCE,
                'description' => 'Jasa pemeliharaan dan dukungan',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_TRAINING,
                'description' => 'Jasa pelatihan dan pendidikan',
            ],
            [
                'name' => DemoMasterData::PRODUCT_CATEGORY_SOFTWARE,
                'description' => 'Produk perangkat lunak dan langganan SaaS',
            ],
        ];

        foreach ($categories as $category) {
            // Idempoten: seed ulang tidak boleh melanggar unique(name).
            DB::table('product_categories')->updateOrInsert(
                ['name' => $category['name']],
                fn (bool $exists) => [
                    'description' => $category['description'],
                    // created_at hanya saat insert, agar seed ulang tidak mengubah tanggal dibuat.
                    ...($exists ? [] : ['created_at' => now()]),
                    'updated_at' => now(),
                ],
            );
        }
    }
}
