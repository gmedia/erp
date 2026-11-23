<?php

namespace Database\Seeders;

use App\Actions\AssetDepreciationRuns\CalculateDepreciationAction;
use App\Actions\AssetDepreciationRuns\PostDepreciationToJournalAction;
use App\Models\Account;
use App\Models\Asset;
use App\Models\AssetDepreciationRun;
use App\Models\CoaVersion;
use App\Models\FiscalYear;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Normalise asset depreciation so the demo data is internally consistent and
 * reconciles with the GL (W7-05):
 *   - every in-service asset (active/maintenance) carries a method, useful
 *     life, depreciation start date and the dedicated expense/accumulated
 *     accounts;
 *   - accumulated depreciation reflects the asset's age (straight line from
 *     the start date up to the previous month), rounded to whole rupiah;
 *   - book value = cost − accumulated for every asset;
 *   - exactly one posted depreciation run exists for the previous month, with
 *     a posted journal (Dr Depreciation Expense / Cr Accumulated Depreciation).
 */
class AssetDepreciationNormalizationSeeder extends Seeder
{
    public function run(): void
    {
        $adminUserId = User::query()
            ->where('email', config('app.admin'))
            ->value('id') ?? User::query()->value('id');

        $fiscalYear = FiscalYear::query()->where('status', 'open')->first();
        $coaVersionId = $fiscalYear
            ? CoaVersion::query()
                ->where('fiscal_year_id', $fiscalYear->id)
                ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
                ->value('id')
            : null;

        $accountId = static fn (string $code): ?int => $coaVersionId
            ? Account::query()->where('coa_version_id', $coaVersionId)->where('code', $code)->value('id')
            : null;

        $expenseAccountId = $accountId('53000');
        $accumulatedAccountId = $accountId('12200');

        if (! $expenseAccountId || ! $accumulatedAccountId) {
            return;
        }

        $periodStart = now()->subMonth()->startOfMonth();
        $periodEnd = now()->subMonth()->endOfMonth();

        $existingRun = $this->findPostedRun($fiscalYear, $periodStart);

        if ($existingRun) {
            // Re-seeding: keep the posted run and its journal; normalise assets
            // through the run period so the values stay identical (idempotent).
            $normaliseUntil = $periodEnd;
        } else {
            // First seed: normalise through the month before the run, then let
            // the run add exactly one more month.
            $normaliseUntil = $periodStart->copy()->subDay()->endOfMonth();
            $this->purgeSeededRuns();
        }

        foreach (Asset::query()->orderBy('id')->get() as $asset) {
            $isInService = in_array($asset->status, ['active', 'maintenance'], true);
            $isDisposed = $asset->status === 'disposed';
            $cost = (float) $asset->purchase_cost;
            $salvage = (float) ($asset->salvage_value ?? 0);

            if ($isDisposed) {
                $this->applyDepreciation($asset, $cost, $expenseAccountId, $accumulatedAccountId);

                continue;
            }

            if (! $isInService && $asset->status !== 'lost') {
                // Draft assets are not capitalised; no depreciation yet.
                $this->applyDepreciation($asset, 0.0, $expenseAccountId, $accumulatedAccountId);

                continue;
            }

            $usefulLife = (int) ($asset->useful_life_months ?? 0);
            if ($usefulLife <= 0) {
                $usefulLife = 60;
            }

            $startDate = $asset->depreciation_start_date
                ?? $asset->purchase_date
                ?? $normaliseUntil;

            // Whole months only, and whole-rupiah monthly amount, so the
            // demo never shows fractional cents (W7-05).
            $months = (int) floor(Carbon::parse($startDate)->startOfDay()->diffInMonths($normaliseUntil->copy()->startOfDay()));
            $monthly = (int) round(($cost - $salvage) / $usefulLife);
            $accumulated = min($cost, (float) ($monthly * $months));

            $asset->forceFill([
                'depreciation_method' => $asset->depreciation_method ?: 'straight_line',
                'depreciation_start_date' => Carbon::parse($startDate)->toDateString(),
                'useful_life_months' => $usefulLife,
                'salvage_value' => $salvage,
            ]);

            $this->applyDepreciation($asset, (float) $accumulated, $expenseAccountId, $accumulatedAccountId);
        }

        if (! $existingRun) {
            $this->createPostedRun($adminUserId, $expenseAccountId, $accumulatedAccountId, $periodStart, $periodEnd);
        }
    }

    /**
     * @param  float  $accumulated  accumulated depreciation *before* the run
     */
    private function applyDepreciation(Asset $asset, float $accumulated, int $expenseAccountId, int $accumulatedAccountId): void
    {
        $cost = (float) $asset->purchase_cost;
        $accumulated = min($cost, round($accumulated));

        $asset->forceFill([
            'depreciation_expense_account_id' => $expenseAccountId,
            'accumulated_depr_account_id' => $accumulatedAccountId,
            'accumulated_depreciation' => $accumulated,
            'book_value' => $cost - $accumulated,
        ])->saveQuietly();
    }

    private function findPostedRun(?FiscalYear $fiscalYear, Carbon $periodStart): ?AssetDepreciationRun
    {
        if (! $fiscalYear) {
            return null;
        }

        $run = AssetDepreciationRun::query()
            ->where('fiscal_year_id', $fiscalYear->id)
            ->where('period_start', $periodStart->toDateString())
            ->where('status', 'posted')
            ->first();

        if (! $run || ! $run->journal_entry_id) {
            return null;
        }

        $journalStatus = JournalEntry::query()->whereKey($run->journal_entry_id)->value('status');

        return $journalStatus === 'posted' ? $run : null;
    }

    private function purgeSeededRuns(): void
    {
        $runs = AssetDepreciationRun::query()->get();

        foreach ($runs as $run) {
            if ($run->journal_entry_id) {
                DB::table('journal_entry_lines')->where('journal_entry_id', $run->journal_entry_id)->delete();
                JournalEntry::query()->whereKey($run->journal_entry_id)->delete();
            }

            DB::table('asset_depreciation_lines')->where('asset_depreciation_run_id', $run->id)->delete();
            $run->delete();
        }
    }

    private function createPostedRun(
        ?int $adminUserId,
        int $expenseAccountId,
        int $accumulatedAccountId,
        Carbon $periodStart,
        Carbon $periodEnd
    ): void {
        $fiscalYear = FiscalYear::query()->where('status', 'open')->first();

        if (! $fiscalYear) {
            return;
        }

        $run = app(CalculateDepreciationAction::class)->execute([
            'fiscal_year_id' => $fiscalYear->id,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ]);

        app(PostDepreciationToJournalAction::class)->execute($run);

        $run->forceFill([
            'created_by' => $adminUserId ?? $run->created_by,
            'posted_by' => $adminUserId ?? $run->posted_by,
            'posted_at' => $run->posted_at ?? now(),
        ])->save();
    }
}
