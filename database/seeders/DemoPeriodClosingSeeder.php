<?php

namespace Database\Seeders;

use App\Actions\PeriodClosings\ClosePeriodAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\PeriodClosing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Menutup tahun fiskal tahun lalu SETELAH seluruh transaksi demo tercatat (#571).
 *
 * Penutupan memakai ClosePeriodAction (alur aplikasi), bukan menulis status langsung,
 * sehingga jurnal penutup dan laba ditahan dibentuk oleh action yang sama dengan produksi.
 * Urutan: tutup 12 bulan (monthly) lalu penutupan tahunan (annual) yang mengunci seluruh FY.
 *
 * Idempoten: periode yang sudah closed dilewati, sehingga seed ulang tidak menutup ganda.
 */
class DemoPeriodClosingSeeder extends Seeder
{
    public function __construct(private ClosePeriodAction $close) {}

    public function run(): void
    {
        $fiscalYear = FiscalYear::query()->where('name', (string) (now()->year - 1))->first();

        if ($fiscalYear === null) {
            return;
        }

        $user = User::query()->where('email', config('app.admin'))->first()
            ?? User::query()->first();

        if ($user === null) {
            return;
        }

        $periodYear = (int) $fiscalYear->start_date->format('Y');
        $retainedEarningsAccountId = $this->retainedEarningsAccountId($fiscalYear);

        if ($retainedEarningsAccountId === null) {
            return;
        }

        Auth::setUser($user);

        foreach (range(1, 12) as $month) {
            $this->closeMonth($fiscalYear, $periodYear, $month, $user->id, $retainedEarningsAccountId);
        }

        $this->closeYear($fiscalYear, $periodYear, $user->id, $retainedEarningsAccountId);

        // Status FY ikut closed setelah seluruh periodenya ditutup, sama seperti kondisi
        // awal data demo (CoaSeeder). Penutupan periode tetap lewat ClosePeriodAction.
        $fiscalYear->update(['status' => 'closed']);
    }

    /**
     * Akun laba ditahan = akun equity aktif daun pada COA version FY tersebut (urut kode).
     */
    private function retainedEarningsAccountId(FiscalYear $fiscalYear): ?int
    {
        $coaVersionId = CoaVersion::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->value('id');

        if ($coaVersionId === null) {
            return null;
        }

        return Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('type', 'equity')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->value('id');
    }

    private function closeMonth(
        FiscalYear $fiscalYear,
        int $periodYear,
        int $month,
        int $userId,
        ?int $retainedEarningsAccountId,
    ): void {
        $closing = PeriodClosing::query()->firstOrCreate(
            [
                'fiscal_year_id' => $fiscalYear->id,
                'period_year' => $periodYear,
                'period_month' => $month,
                'closing_type' => 'monthly',
            ],
            [
                'status' => 'draft',
                'retained_earnings_account_id' => $retainedEarningsAccountId,
                'created_by' => $userId,
            ],
        );

        if ($closing->isClosed()) {
            return;
        }

        $this->close->execute($closing);
    }

    private function closeYear(
        FiscalYear $fiscalYear,
        int $periodYear,
        int $userId,
        ?int $retainedEarningsAccountId,
    ): void {
        $closing = PeriodClosing::query()->firstOrCreate(
            [
                'fiscal_year_id' => $fiscalYear->id,
                'period_year' => $periodYear,
                'closing_type' => 'annual',
            ],
            [
                'period_month' => null,
                'status' => 'draft',
                'retained_earnings_account_id' => $retainedEarningsAccountId,
                'created_by' => $userId,
            ],
        );

        if ($closing->isClosed()) {
            return;
        }

        $this->close->execute($closing);
    }
}
