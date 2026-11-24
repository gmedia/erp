<?php

namespace Database\Seeders;

/**
 * Nama dan kode master demo (cabang, gudang) dalam satu tempat.
 * Seeder dan pencari data demo wajib memakai konstanta ini, bukan string literal,
 * agar rename nama demo hanya terjadi di satu tempat.
 */
final class DemoMasterData
{
    public const BRANCH_HEAD_OFFICE = 'Kantor Pusat';

    /** Nama lama (Inggris) cabang pusat demo; dipakai untuk rename baris demo. */
    public const LEGACY_BRANCH_HEAD_OFFICE = 'Head Office';

    public const WAREHOUSE_MAIN_CODE = 'MAIN';

    public const WAREHOUSE_MAIN_NAME = 'Gudang Utama';

    public const WAREHOUSE_TRANSIT_CODE = 'TRN';

    public const WAREHOUSE_TRANSIT_NAME = 'Gudang Transit';

    public const WAREHOUSE_RETURN_CODE = 'RTN';

    public const WAREHOUSE_RETURN_NAME = 'Gudang Retur';

    public const WAREHOUSE_PRODUCTION_CODE = 'PRD';

    public const WAREHOUSE_PRODUCTION_NAME = 'Gudang Produksi';

    // Kategori produk demo (Bahasa Indonesia, W7-17).
    public const PRODUCT_CATEGORY_ELECTRONICS = 'Elektronik';

    public const PRODUCT_CATEGORY_OFFICE_SUPPLIES = 'Perlengkapan Kantor';

    public const PRODUCT_CATEGORY_FURNITURE = 'Furnitur';

    public const PRODUCT_CATEGORY_RAW_WOOD = 'Bahan Baku Kayu';

    public const PRODUCT_CATEGORY_RAW_HARDWARE = 'Bahan Baku Perangkat Keras';

    public const PRODUCT_CATEGORY_FINISHED_FURNITURE = 'Barang Jadi Furnitur';

    public const PRODUCT_CATEGORY_CONSULTING = 'Jasa Konsultasi';

    public const PRODUCT_CATEGORY_MAINTENANCE = 'Jasa Pemeliharaan';

    public const PRODUCT_CATEGORY_TRAINING = 'Jasa Pelatihan';

    public const PRODUCT_CATEGORY_SOFTWARE = 'Perangkat Lunak & SaaS';

    // Kategori supplier demo.
    public const SUPPLIER_CATEGORY_IT_SERVICES = 'Jasa TI';

    // Satuan demo.
    public const UNIT_BOX = 'Kotak';

    public const UNIT_SET = 'Perangkat';

    public const UNIT_SHEET = 'Lembar';

    public const UNIT_SERVICE = 'Jasa';

    // Departemen dan jabatan demo yang dirujuk oleh seeder pengguna demo.
    public const DEPARTMENT_OPERATIONS = 'Operasional';

    public const DEPARTMENT_HR = 'SDM';

    public const DEPARTMENT_FINANCE = 'Keuangan';

    public const DEPARTMENT_ENGINEERING = 'Teknik';

    public const POSITION_DRIVER = 'Pengemudi';

    public const POSITION_MANAGER = 'Manajer';

    public const POSITION_DIRECTOR = 'Direktur';

    public const POSITION_LEAD = 'Koordinator';

    public const POSITION_SENIOR_DEVELOPER = 'Pengembang Senior';

    private function __construct()
    {
        // Intentionally empty. Kelas ini hanya berisi konstanta.
    }
}
