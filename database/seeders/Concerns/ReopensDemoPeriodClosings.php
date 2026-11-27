<?php

namespace Database\Seeders\Concerns;

/**
 * Titik masuk seeder demo (#571). Trait ini TIDAK lagi membuka periode yang ditutup:
 * jurnal demo diposting lewat validasi periode aplikasi yang normal. Tahun lalu dibuat
 * open (CoaSeeder) dan ditutup sekali oleh DemoPeriodClosingSeeder di akhir seed.
 * Seeder yang memakai trait ini cukup mengisi seedDemoData().
 */
trait ReopensDemoPeriodClosings
{
    /** Titik masuk seeder: isi seeder ada di seedDemoData(). */
    public function run(): void
    {
        $this->seedDemoData();
    }

    abstract protected function seedDemoData(): void;
}
