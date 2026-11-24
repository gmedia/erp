<?php

namespace Database\Seeders\Concerns;

use App\Models\FiscalYear;
use App\Models\PeriodClosing;
use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Seeder demo yang memposting jurnal harus bisa berjalan ulang walau periode demo
 * sudah ditutup (#423). Trait ini membuka sementara HANYA baris period closing
 * milik data demo (monthly bulan 1-2 pada tahun fiskal yang sama dengan
 * GlExtendedSampleDataSeeder), menjalankan posting, lalu mengembalikan status semula.
 * Periode yang ditutup pengguna tidak disentuh.
 */
trait ReopensDemoPeriodClosings
{
    /**
     * @param  Closure(): void  $callback
     */
    protected function withDemoPeriodsReopened(FiscalYear $fiscalYear, Closure $callback): void
    {
        DB::transaction(function () use ($fiscalYear, $callback): void {
            $demoClosings = PeriodClosing::query()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->where('closing_type', 'monthly')
                ->where('period_year', (int) $fiscalYear->start_date->format('Y'))
                ->whereIn('period_month', [1, 2])
                ->where('status', 'closed')
                ->lockForUpdate()
                ->get();

            $demoClosings->each(fn (PeriodClosing $closing) => $closing->update(['status' => 'reopened']));

            try {
                $callback();
            } finally {
                $demoClosings->each(fn (PeriodClosing $closing) => $closing->update(['status' => 'closed']));
            }
        });
    }
}
