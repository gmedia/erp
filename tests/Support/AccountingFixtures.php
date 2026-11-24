<?php

use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;

/**
 * COA aktif lengkap untuk jurnal GR (Issue #416): tahun fiskal yang memuat periode
 * jurnal, serta akun persediaan 11300 dan GR/IR 21150. Tanpa ini konfirmasi GR
 * ditolak 422 karena akun kontrol tidak ditemukan.
 *
 * @param  string  $year  Tahun fiskal yang memuat tanggal jurnal test (default tahun berjalan).
 */
function createAccountingControlFixture(?string $year = null): CoaVersion
{
    $year ??= (string) now()->year;
    $previous = (int) $year - 1;

    // Tahun sebelumnya ikut dibuat: test memundurkan tanggal dokumen (mis. 10 hari lalu)
    // yang bisa jatuh di tahun lalu saat awal tahun.
    FiscalYear::factory()->create([
        'name' => (string) $previous,
        'start_date' => "{$previous}-01-01",
        'end_date' => "{$previous}-12-31",
        'status' => 'open',
    ]);

    $fiscalYear = FiscalYear::factory()->create([
        'name' => $year,
        'start_date' => "{$year}-01-01",
        'end_date' => "{$year}-12-31",
        'status' => 'open',
    ]);

    // Satu COA aktif saja: ResolveControlAccountAction menolak bila ada lebih dari satu.
    $coaVersion = CoaVersion::factory()->create([
        'fiscal_year_id' => $fiscalYear->id,
        'status' => 'active',
    ]);

    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '11300',
        'name' => 'Persediaan',
        'type' => 'asset',
        'normal_balance' => 'debit',
        'is_active' => true,
    ]);

    Account::factory()->create([
        'coa_version_id' => $coaVersion->id,
        'code' => '21150',
        'name' => 'Barang Diterima Belum Ditagih',
        'type' => 'liability',
        'normal_balance' => 'credit',
        'is_active' => true,
    ]);

    return $coaVersion;
}
