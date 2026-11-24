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

    private function __construct()
    {
        // Intentionally empty. Kelas ini hanya berisi konstanta.
    }
}
