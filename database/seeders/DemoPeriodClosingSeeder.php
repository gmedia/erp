<?php

namespace Database\Seeders;

use App\Actions\JournalEntries\CreateJournalEntryAction;
use App\Actions\PeriodClosings\ClosePeriodAction;
use App\Models\Account;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\PeriodClosing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
    public function __construct(
        private ClosePeriodAction $close,
        private CreateJournalEntryAction $createJournalEntry,
    ) {}

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

        // Jurnal demo diposting pada tanggal dokumennya, bukan saat seeder berjalan,
        // agar posted_at tidak melewati closed_at periode (#494, W9-494).
        $this->backdatePostedJournals($fiscalYear);

        // Saldo akun neraca tahun lalu dibawa ke COA tahun berjalan lewat account mapping,
        // sehingga laporan yang hanya membaca COA aktif tetap melihat saldo awal (#571).
        $this->carryForwardBalanceSheet($fiscalYear);

        // Status FY ikut closed setelah seluruh periodenya ditutup, sama seperti kondisi
        // awal data demo (CoaSeeder). Penutupan periode tetap lewat ClosePeriodAction.
        $fiscalYear->update(['status' => 'closed']);

        // COA tahun lalu diarsipkan setelah FY-nya ditutup (siklus hidup tunggal, #571).
        // Tidak ada action khusus untuk status COA: halaman COA Versions juga mengubah
        // status lewat update model (CoaVersionController::update), jadi jalur yang sama.
        CoaVersion::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('status', 'active')
            ->get()
            ->each(fn (CoaVersion $coaVersion) => $coaVersion->update(['status' => 'archived']));
    }

    /**
     * Jurnal saldo awal di COA tahun berjalan: seluruh saldo akun neraca (asset, liability,
     * equity) tahun yang ditutup dipindahkan ke akun padanannya. Padanan dicari dari kode
     * yang sama di COA aktif; bila kode tidak ada (mis. 11100 dipecah menjadi 11110/11120),
     * dipakai account_mappings dan target berurut kode pertama (mapping "primary").
     *
     * Idempoten: jurnal dengan referensi OPENING-FY-{id} tidak dibuat ulang.
     */
    private function carryForwardBalanceSheet(FiscalYear $closedYear): void
    {
        $nextYear = FiscalYear::query()
            ->where('start_date', '>', $closedYear->end_date)
            ->orderBy('start_date')
            ->first();

        if ($nextYear === null) {
            return;
        }

        $targetCoa = CoaVersion::query()
            ->where('fiscal_year_id', $nextYear->id)
            ->where('status', 'active')
            ->first();

        if ($targetCoa === null) {
            return;
        }

        $reference = 'OPENING-FY-' . $nextYear->id;

        if (JournalEntry::query()->where('reference', $reference)->exists()) {
            return;
        }

        $rows = DB::table('journal_entry_lines')
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->join('accounts', 'accounts.id', '=', 'journal_entry_lines.account_id')
            ->where('journal_entries.fiscal_year_id', $closedYear->id)
            ->where('journal_entries.status', 'posted')
            ->whereIn('accounts.type', ['asset', 'liability', 'equity'])
            ->groupBy('accounts.id', 'accounts.code', 'journal_entry_lines.branch_id')
            ->select('accounts.id', 'accounts.code', 'journal_entry_lines.branch_id')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.debit), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit), 0) as credit_total')
            ->get();

        $lines = [];

        foreach ($rows as $row) {
            $net = round((float) $row->debit_total - (float) $row->credit_total, 2);

            if (abs($net) < 0.005) {
                continue;
            }

            $target = $this->openingTargetAccount((int) $row->id, (string) $row->code, $targetCoa);

            if ($target === null) {
                throw ValidationException::withMessages([
                    'opening_balance' => "Akun {$row->code} tidak punya padanan di COA {$targetCoa->name}.",
                ]);
            }

            $lines[] = [
                'account_id' => $target->id,
                'branch_id' => $row->branch_id,
                'debit' => $net > 0 ? $net : 0,
                'credit' => $net < 0 ? -$net : 0,
                'memo' => 'Saldo awal dari COA ' . $closedYear->name,
            ];
        }

        if ($lines === []) {
            return;
        }

        $totalDebit = array_sum(array_column($lines, 'debit'));
        $totalCredit = array_sum(array_column($lines, 'credit'));

        if (abs($totalDebit - $totalCredit) > 0.005) {
            throw ValidationException::withMessages([
                'opening_balance' => 'Saldo awal tidak seimbang: debit '
                    . $totalDebit . ' vs kredit ' . $totalCredit,
            ]);
        }

        $this->createJournalEntry->execute([
            'entry_date' => $nextYear->start_date->toDateString(),
            'reference' => $reference,
            'description' => 'Saldo awal tahun fiskal ' . $nextYear->name,
            'status' => 'posted',
            'journal_type' => 'system',
            'lines' => $lines,
        ]);
    }

    private function openingTargetAccount(int $sourceAccountId, string $code, CoaVersion $targetCoa): ?Account
    {
        $sameCode = Account::query()
            ->where('coa_version_id', $targetCoa->id)
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if ($sameCode !== null) {
            return $sameCode;
        }

        return Account::query()
            ->join('account_mappings', 'account_mappings.target_account_id', '=', 'accounts.id')
            ->where('account_mappings.source_account_id', $sourceAccountId)
            ->where('accounts.coa_version_id', $targetCoa->id)
            ->where('accounts.is_active', true)
            ->orderBy('accounts.code')
            ->select('accounts.*')
            ->first();
    }

    /**
     * Akun laba ditahan = akun 32000 (Retained Earnings) pada COA version FY tersebut.
     *
     * Jangan mengambil akun equity daun pertama (urut kode): pada COA demo akun itu
     * adalah 31000 Owner Capital, sehingga laba/rugi penutupan tahunan mengurangi modal
     * disetor dan membuat saldo modal tampak negatif di laporan komparatif (#571 C8).
     * Bila 32000 tidak ada, baru jatuh ke akun equity daun pertama.
     */
    private function retainedEarningsAccountId(FiscalYear $fiscalYear): ?int
    {
        $coaVersionId = CoaVersion::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->value('id');

        if ($coaVersionId === null) {
            return null;
        }

        $retainedEarningsId = Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('code', '32000')
            ->where('is_active', true)
            ->value('id');

        if ($retainedEarningsId !== null) {
            return (int) $retainedEarningsId;
        }

        return Account::query()
            ->where('coa_version_id', $coaVersionId)
            ->where('type', 'equity')
            ->where('is_active', true)
            ->whereDoesntHave('children')
            ->orderBy('code')
            ->value('id');
    }

    /**
     * Samakan posted_at dengan entry_date untuk seluruh jurnal posted tahun yang
     * ditutup. Data demo tidak "diposting" di masa depan relatif terhadap periode
     * yang sudah ditutup (#494, W9-494).
     */
    private function backdatePostedJournals(FiscalYear $fiscalYear): void
    {
        DB::table('journal_entries')
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('status', 'posted')
            ->update(['posted_at' => DB::raw('entry_date')]);
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

        // Penutupan bulanan dicatat pada awal bulan berikutnya, bukan saat seed dijalankan.
        $closing->forceFill([
            'closed_at' => Carbon::create($periodYear, $month, 1)->addMonth()->startOfDay(),
        ])->saveQuietly();
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
