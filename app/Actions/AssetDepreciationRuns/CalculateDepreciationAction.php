<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use App\Models\FiscalYear;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateDepreciationAction
{
    public function execute(array $data): AssetDepreciationRun
    {
        return DB::transaction(function () use ($data) {
            $this->assertSingleCalendarMonth($data);

            // Kunci tahun fiscal agar dua run bersamaan tidak lolos cek tumpang-tindih.
            FiscalYear::whereKey($data['fiscal_year_id'])->lockForUpdate()->first();

            // Tolak periode yang beririsan dengan run aktif (draft, calculated, posted).
            $overlappingRun = AssetDepreciationRun::where('fiscal_year_id', $data['fiscal_year_id'])
                ->where('period_start', '<=', $data['period_end'])
                ->where('period_end', '>=', $data['period_start'])
                ->whereIn('status', ['draft', 'calculated', 'posted'])
                ->first();

            if ($overlappingRun) {
                throw ValidationException::withMessages([
                    'period_start' => __('asset_depreciation_runs.overlapping_period'),
                ]);
            }

            // Run calculated yang belum diposting menyimpan akumulasi basi. Menghitung bulan berikutnya
            // di atasnya membuat posting run kedua menimpa accumulated_depreciation dengan nilai yang sama.
            // Cek ini global (lintas tahun fiskal). Baris run calculated dikunci (urut id) agar
            // posting bersamaan tidak lolos cek, dan exists() tidak mengabaikan lock.
            $unpostedRun = AssetDepreciationRun::where('status', 'calculated')
                ->orderBy('id')
                ->lockForUpdate()
                ->first(['id']);

            if ($unpostedRun !== null) {
                throw ValidationException::withMessages([
                    'period_start' => __('asset_depreciation_runs.unposted_run_exists'),
                ]);
            }

            // Unique (fiscal_year_id, period_start, period_end) tidak mengenal status, jadi run void
            // untuk periode persis sama dihidupkan kembali sebagai calculated, bukan di-insert ulang.
            $voidRun = AssetDepreciationRun::where('fiscal_year_id', $data['fiscal_year_id'])
                ->where('period_start', $data['period_start'])
                ->where('period_end', $data['period_end'])
                ->where('status', 'void')
                ->first();

            if ($voidRun) {
                AssetDepreciationLine::where('asset_depreciation_run_id', $voidRun->id)->delete();
                $voidRun->update([
                    'status' => 'calculated',
                    'created_by' => Auth::id(),
                ]);
                $run = $voidRun;
            } else {
                $run = AssetDepreciationRun::create([
                    'fiscal_year_id' => $data['fiscal_year_id'],
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                    'status' => 'calculated',
                    'created_by' => Auth::id(),
                ]);
            }

            // Find eligible assets (optionally limited to $data['asset_codes'], used by seeders)
            $assets = Asset::whereIn('status', ['active', 'maintenance'])
                ->whereNotNull('depreciation_method')
                ->where('depreciation_start_date', '<=', $data['period_end'])
                ->where('purchase_cost', '>', DB::raw('COALESCE(salvage_value, 0)'))
                ->where('useful_life_months', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('book_value')
                        ->orWhereRaw('book_value > COALESCE(salvage_value, 0)');
                })
                ->when(
                    isset($data['asset_codes']),
                    fn ($q) => $q->whereIn('asset_code', $data['asset_codes'])
                )
                ->get();

            $lines = [];

            foreach ($assets as $asset) {
                $cost = (float) $asset->purchase_cost;
                $salvage = (float) $asset->salvage_value;
                $usefulLife = (int) $asset->useful_life_months;

                // Whole-rupiah monthly depreciation (W7-05).
                $monthlyAmount = round(($cost - $salvage) / $usefulLife);

                $accumulatedBefore = (float) $asset->accumulated_depreciation;
                $bookValueBefore = (float) ($asset->book_value ?? ($cost - $accumulatedBefore));

                // Adjust if amount exceeds remaining depreciable value
                $maxDepreciable = $bookValueBefore - $salvage;

                if ($maxDepreciable <= 0) {
                    continue; // Already fully depreciated
                }

                // Sisa hanya dihabiskan pada bulan terakhir umur aset, atau bila sisanya tak cukup
                // untuk satu bulanan penuh. Bulan lain selalu membebankan tepat satu bulanan.
                // Hanya run posted yang sudah mengubah akumulasi aset; calculated/void/draft tidak.
                $monthsRemaining = $usefulLife - (int) AssetDepreciationLine::where('asset_id', $asset->id)
                    ->whereHas('run', fn ($q) => $q->where('status', 'posted'))
                    ->count();
                $isLastMonth = $monthsRemaining <= 1 || $maxDepreciable <= $monthlyAmount;
                $amount = round($isLastMonth ? $maxDepreciable : $monthlyAmount);

                $accumulatedAfter = $accumulatedBefore + $amount;
                $bookValueAfter = $bookValueBefore - $amount;

                $lines[] = [
                    'asset_depreciation_run_id' => $run->id,
                    'asset_id' => $asset->id,
                    'amount' => $amount,
                    'accumulated_before' => $accumulatedBefore,
                    'accumulated_after' => $accumulatedAfter,
                    'book_value_after' => $bookValueAfter,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($lines)) {
                AssetDepreciationLine::insert($lines);
            }

            return $run->loadCount('lines');
        });
    }

    /**
     * Satu run hanya boleh mencakup satu bulan kalender agar beban tiap bulan tercatat sekali.
     *
     * @param  array{period_start: string, period_end: string}  $data
     */
    private function assertSingleCalendarMonth(array $data): void
    {
        $start = Carbon::parse($data['period_start']);
        $end = Carbon::parse($data['period_end']);

        if (! $start->isSameMonth($end)) {
            throw ValidationException::withMessages([
                'period_end' => __('asset_depreciation_runs.single_month_only'),
            ]);
        }
    }
}
