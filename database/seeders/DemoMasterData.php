<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Support\Collection;

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

    /**
     * Barang demo stok (opname, penyesuaian, transfer) memakai produk katalog yang sudah ada
     * (#503 butir 1). Kode `INV-2026-*` lama adalah duplikat dan dibersihkan seeder.
     *
     * @var list<string>
     */
    public const STOCK_DEMO_PRODUCT_CODES = ['EL-PER-001', 'EL-PER-002'];

    /** @var list<string> Kode duplikat lama (#503 butir 1). */
    public const LEGACY_STOCK_DEMO_PRODUCT_CODES = ['INV-2026-001', 'INV-2026-002', 'INV-2026-003'];

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

    public const SUPPLIER_CATEGORY_LOGISTICS = 'Logistik';

    /**
     * Kategori barang yang boleh dipasok tiap kategori supplier demo (#459 butir 8).
     * Supplier Logistik hanya mengirim jasa angkut, tidak memasok barang fisik.
     *
     * @var array<string, list<string>>
     */
    public const SUPPLIER_PRODUCT_CATEGORY_MAP = [
        'Perlengkapan Kantor' => [self::PRODUCT_CATEGORY_OFFICE_SUPPLIES],
        'Elektronik' => [self::PRODUCT_CATEGORY_ELECTRONICS],
        'Furnitur' => [
            self::PRODUCT_CATEGORY_RAW_WOOD,
            self::PRODUCT_CATEGORY_RAW_HARDWARE,
            self::PRODUCT_CATEGORY_FINISHED_FURNITURE,
            self::PRODUCT_CATEGORY_FURNITURE,
        ],
        self::SUPPLIER_CATEGORY_IT_SERVICES => [self::PRODUCT_CATEGORY_SOFTWARE],
    ];

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

    public const DEPARTMENT_SALES = 'Penjualan';

    public const POSITION_DRIVER = 'Pengemudi';

    public const POSITION_MANAGER = 'Manajer';

    public const POSITION_DIRECTOR = 'Direktur';

    public const POSITION_LEAD = 'Koordinator';

    public const POSITION_SENIOR_DEVELOPER = 'Pengembang Senior';

    private function __construct()
    {
        // Intentionally empty. Kelas ini hanya berisi konstanta.
    }

    /**
     * Ambil produk demo stok sesuai urutan konstanta (urutan sama di semua seeder stok).
     *
     * @return Collection<int, Product>
     */
    public static function stockDemoProducts(): Collection
    {
        $byCode = Product::query()
            ->whereIn('code', self::STOCK_DEMO_PRODUCT_CODES)
            ->get()
            ->keyBy('code');

        return collect(self::STOCK_DEMO_PRODUCT_CODES)
            ->map(fn (string $code) => $byCode->get($code))
            ->filter()
            ->values();
    }
}
