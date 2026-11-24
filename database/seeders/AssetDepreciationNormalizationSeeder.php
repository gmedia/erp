<?php

namespace Database\Seeders;

use App\Actions\AccountingPosting\PostAssetLossJournalAction;
use App\Actions\AssetDepreciationRuns\CalculateDepreciationAction;
use App\Actions\AssetDepreciationRuns\PostDepreciationToJournalAction;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntryLine;
use App\Models\User;
use Database\Seeders\Concerns\ReopensDemoPeriodClosings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Menormalkan depresiasi aset demo (W7-05, #357).
 *
 * Akumulasi penyusutan register TIDAK ditulis langsung oleh seeder. Sumbernya hanya:
 *   1. saldo awal per aset dari PostAssetAcquisitionJournalAction (aset lama), dan
 *   2. run depresiasi bulanan yang dibuat dan diposting lewat action aplikasi
 *      (CalculateDepreciationAction + PostDepreciationToJournalAction).
 *
 * Run dibuat untuk setiap bulan fiscal year terbuka, dari bulan pertama FY
 * sampai bulan terakhir yang sudah lewat. Aset draft tidak diposting.
 */
class AssetDepreciationNormalizationSeeder extends Seeder
{
    use ReopensDemoPeriodClosings;

    public function run(): void
    {
        $fiscalYear = FiscalYear::query()->where('status', 'open')->orderBy('start_date')->first();

        if (! $fiscalYear) {
            return;
        }

        $this->withDemoPeriodsReopened($fiscalYear, fn () => $this->postDemoDepreciation($fiscalYear));
    }

    private function postDemoDepreciation(FiscalYear $fiscalYear): void
    {
        $expenseAccountId = $this->accountId($fiscalYear, '53000');
        $accumulatedAccountId = $this->accountId($fiscalYear, '12200');

        if (! $expenseAccountId || ! $accumulatedAccountId) {
            return;
        }

        $adminUserId = User::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? User::query()->value('id');

        $this->stampAssetMetadata($expenseAccountId, $accumulatedAccountId);

        $fiscalStart = Carbon::parse($fiscalYear->start_date)->startOfMonth();
        $fiscalEnd = Carbon::parse($fiscalYear->end_date)->startOfMonth();
        $lastClosedMonth = now()->subMonth()->startOfMonth();

        for ($cursor = $fiscalStart->copy(); $cursor->lessThanOrEqualTo($fiscalEnd) && $cursor->lessThanOrEqualTo($lastClosedMonth); $cursor->addMonth()) {
            $this->postMonthlyRun($fiscalYear, $cursor->copy(), $adminUserId);
        }

        $this->syncRegisterFromPostedSources();

        // Jurnal aset hilang hanya setelah akumulasi register final. Dipost
        // lebih awal, akun 12200 tidak ikut didebit (#357, FA-000008).
        $this->demoAssets()->get()->each(function (Asset $asset): void {
            $lossJournal = app(PostAssetLossJournalAction::class);

            if ($lossJournal->isPostable($asset)) {
                $lossJournal->execute($asset);
            }
        });
    }

    /**
     * Hanya aset demo (AssetSampleDataSeeder::DEMO_ASSET_CODES). Aset buatan
     * pengguna, beserta run dan jurnal depresiasinya, tidak disentuh (#357).
     */
    private function demoAssets()
    {
        return Asset::query()
            ->whereIn('asset_code', AssetSampleDataSeeder::DEMO_ASSET_CODES)
            ->orderBy('id');
    }

    /**
     * Register (accumulated_depreciation, book_value) = saldo awal opening
     * (jurnal ACQ-* dari PostAssetAcquisitionJournalAction) + total run terposting.
     * Nilai hardcode di sample aset digantikan agar register sama dengan GL.
     */
    private function syncRegisterFromPostedSources(): void
    {
        $this->demoAssets()->get()->each(function (Asset $asset): void {
            if (in_array($asset->status, ['draft', 'cancelled'], true)) {
                return;
            }

            $openingAccumulated = (float) JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entries.reference', 'ACQ-' . $asset->asset_code)
                ->where('journal_entries.status', 'posted')
                ->where('journal_entry_lines.account_id', $asset->accumulated_depr_account_id)
                ->sum('journal_entry_lines.credit');

            $runAccumulated = (float) AssetDepreciationLine::query()
                ->join('asset_depreciation_runs', 'asset_depreciation_runs.id', '=', 'asset_depreciation_lines.asset_depreciation_run_id')
                ->where('asset_depreciation_lines.asset_id', $asset->id)
                ->where('asset_depreciation_runs.status', 'posted')
                ->sum('asset_depreciation_lines.amount');

            $accumulated = $openingAccumulated + $runAccumulated;
            $cost = (float) $asset->purchase_cost;

            $asset->forceFill([
                'accumulated_depreciation' => $accumulated,
                'book_value' => $cost - $accumulated,
            ])->saveQuietly();
        });
    }

    private function accountId(FiscalYear $fiscalYear, string $code): ?int
    {
        $coaVersionId = CoaVersion::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
            ->value('id');

        return $coaVersionId
            ? Account::query()->where('coa_version_id', $coaVersionId)->where('code', $code)->value('id')
            : null;
    }

    /**
     * Hanya metadata konfigurasi depresiasi. Kolom nilai (accumulated_depreciation,
     * book_value) tidak disentuh; keduanya dikelola opening dan run.
     */
    private function stampAssetMetadata(int $expenseAccountId, int $accumulatedAccountId): void
    {
        $this->demoAssets()->get()->each(function (Asset $asset) use ($expenseAccountId, $accumulatedAccountId): void {
            if (in_array($asset->status, ['draft', 'cancelled'], true)) {
                return;
            }

            $asset->forceFill([
                'depreciation_method' => $asset->depreciation_method ?: 'straight_line',
                'depreciation_start_date' => $asset->depreciation_start_date ?? $asset->purchase_date,
                'useful_life_months' => (int) $asset->useful_life_months > 0 ? $asset->useful_life_months : 60,
                'salvage_value' => $asset->salvage_value ?? 0,
                'depreciation_expense_account_id' => $expenseAccountId,
                'accumulated_depr_account_id' => $accumulatedAccountId,
            ])->saveQuietly();
        });
    }

    private function postMonthlyRun(FiscalYear $fiscalYear, Carbon $periodStart, ?int $adminUserId): void
    {
        $periodEnd = $periodStart->copy()->endOfMonth();

        $exists = AssetDepreciationRun::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('period_start', $periodStart->toDateString())
            ->whereIn('status', ['draft', 'calculated', 'posted'])
            ->exists();

        if ($exists) {
            return;
        }

        $run = app(CalculateDepreciationAction::class)->execute([
            'fiscal_year_id' => $fiscalYear->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'asset_codes' => AssetSampleDataSeeder::DEMO_ASSET_CODES,
        ]);

        if ($run->lines_count === 0) {
            return;
        }

        app(PostDepreciationToJournalAction::class)->execute($run);

        $run->forceFill([
            'created_by' => $adminUserId ?? $run->created_by,
            'posted_by' => $adminUserId ?? $run->posted_by,
            'posted_at' => $run->posted_at ?? now(),
        ])->save();
    }
}
