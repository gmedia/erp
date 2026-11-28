<?php

namespace App\Support\Demo;

use App\Models\CoaVersion;
use App\Models\FiscalYear;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Jendela demo 13 bulan relatif terhadap tanggal seed (#571).
 *
 * Jendela = bulan berjalan + 12 bulan sebelumnya, sehingga tiap bulan dalam jendela
 * punya aktivitas transaksi (dibandingkan dengan bulan lalu dan bulan yang sama tahun lalu).
 * Semua tanggal dan nilai dihitung deterministik dari indeks bulan, bukan acak, agar seed
 * ulang menghasilkan data yang sama (idempoten).
 *
 * Satu-satunya tempat yang menentukan pola musiman volume dan nilai demo.
 */
final class DemoCalendar
{
    /** Jumlah bulan dalam jendela demo (bulan berjalan + 12 bulan sebelumnya). */
    public const MONTHS = 13;

    /**
     * Faktor volume per bulan dalam jendela (indeks 0 = 12 bulan lalu, 12 = bulan berjalan).
     * Mengikuti pola musiman wajar: naik-turun bertahap, bukan konstan.
     *
     * @var list<float>
     */
    private const VOLUME_FACTORS = [
        0.78, 0.92, 0.85, 1.05, 1.12, 0.98, 0.88, 1.18, 1.25, 1.04, 0.96, 1.30, 1.10,
    ];

    /**
     * Pengganda nilai dokumen per bulan (kenaikan bertahap tahun ke tahun dan pola musiman).
     *
     * @var list<float>
     */
    private const VALUE_FACTORS = [
        0.85, 0.95, 0.90, 1.02, 1.08, 1.00, 0.93, 1.12, 1.18, 1.06, 1.00, 1.22, 1.14,
    ];

    /**
     * Mulai jendela: awal bulan 12 bulan sebelum bulan berjalan.
     */
    public static function windowStart(?Carbon $anchor = null): Carbon
    {
        return ($anchor ?? now())->copy()->startOfMonth()->subMonths(self::MONTHS - 1);
    }

    /**
     * Daftar bulan dalam jendela, urut dari yang tertua.
     *
     * @return list<Carbon>
     */
    public static function months(?Carbon $anchor = null): array
    {
        $start = self::windowStart($anchor);
        $months = [];

        for ($index = 0; $index < self::MONTHS; $index++) {
            $months[] = $start->copy()->addMonths($index);
        }

        return $months;
    }

    /**
     * Tanggal pada bulan indeks $index (0..12) di jendela, hari ke-$day (dibatasi akhir bulan).
     */
    public static function dateInMonth(int $index, int $day, ?Carbon $anchor = null): Carbon
    {
        self::assertIndex($index);

        $month = self::windowStart($anchor)->addMonths($index);

        return $month->copy()->day(min(max($day, 1), $month->daysInMonth));
    }

    /**
     * Jumlah dokumen dasar per bulan dikali faktor volume, minimal 1 agar setiap bulan
     * punya aktivitas.
     */
    public static function volume(int $index, int $baseCount): int
    {
        self::assertIndex($index);

        return max(1, (int) round($baseCount * self::VOLUME_FACTORS[$index]));
    }

    /**
     * Nilai dokumen dasar dikali faktor nilai bulan, dibulatkan ke rupiah penuh.
     */
    public static function amount(int $index, float $baseAmount): float
    {
        self::assertIndex($index);

        return round($baseAmount * self::VALUE_FACTORS[$index], 0);
    }

    /**
     * Indeks bulan jendela yang memuat tanggal, atau null bila di luar jendela.
     */
    public static function indexOf(Carbon $date, ?Carbon $anchor = null): ?int
    {
        $offset = (int) self::windowStart($anchor)->diffInMonths($date->copy()->startOfMonth(), false);

        return $offset >= 0 && $offset < self::MONTHS ? $offset : null;
    }

    /**
     * Tahun fiskal yang dicakup jendela demo (tahun lalu dan tahun berjalan bila jendela lintas tahun).
     *
     * @return list<int>
     */
    public static function fiscalYears(?Carbon $anchor = null): array
    {
        $years = [];

        foreach (self::months($anchor) as $month) {
            $years[$month->year] = $month->year;
        }

        return array_values($years);
    }

    /**
     * FY tahun berjalan (nama = tahun kalender bulan berjalan). Dipakai seeder transaksi agar
     * memilih FY secara deterministik, bukan "FY open pertama" yang bisa jatuh ke tahun lalu.
     */
    public static function currentFiscalYear(?Carbon $anchor = null): ?FiscalYear
    {
        return FiscalYear::query()
            ->where('name', (string) ($anchor ?? now())->year)
            ->first();
    }

    /**
     * COA version aktif untuk FY berjalan. Seeder dokumen memakai ini, bukan "COA active
     * pertama": COA tahun lalu juga active sampai DemoPeriodClosingSeeder mengarsipkannya (#571).
     */
    public static function currentCoaVersionId(?Carbon $anchor = null): ?int
    {
        $fiscalYear = self::currentFiscalYear($anchor);

        if ($fiscalYear === null) {
            return null;
        }

        return CoaVersion::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('status', 'active')
            ->value('id');
    }

    private static function assertIndex(int $index): void
    {
        if ($index < 0 || $index >= self::MONTHS) {
            throw new InvalidArgumentException("Indeks bulan demo di luar jendela: {$index}");
        }
    }
}
