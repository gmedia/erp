<?php

namespace App\Actions\PeriodClosings;

use App\Models\PeriodClosing;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Guard bersama penguncian periode (#423).
 *
 * Jurnal bertanggal di periode yang berstatus closed ditolak 422. Monthly mengunci
 * bulannya; annual mengunci seluruh tahun fiskal (fiscal_year_id), bukan tahun kalender.
 * Status reopened/draft tidak mengunci.
 */
class AssertPeriodOpenForPostingAction
{
    public function execute(int $fiscalYearId, string $entryDate): void
    {
        $date = Carbon::parse($entryDate);

        if ($this->isLocked($fiscalYearId, $date)) {
            throw ValidationException::withMessages([
                'entry_date' => __('period_closings.posting_locked', ['date' => $date->toDateString()]),
            ]);
        }
    }

    /**
     * Apakah tanggal $date terkunci penutupan (monthly bulan itu, atau annual seluruh FY).
     * Dipakai juga oleh seeder demo agar dokumen di periode tertutup dilewati, bukan gagal (#571).
     */
    public function isLocked(int $fiscalYearId, Carbon $date): bool
    {
        return PeriodClosing::query()
            ->where('fiscal_year_id', $fiscalYearId)
            ->where('status', 'closed')
            ->where(function ($query) use ($date): void {
                $query->where(function ($monthly) use ($date): void {
                    $monthly->where('closing_type', 'monthly')
                        ->where('period_year', $date->year)
                        ->where('period_month', $date->month);
                })->orWhere('closing_type', 'annual');
            })
            ->exists();
    }
}
