<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use App\Models\FiscalYear;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateDepreciationAction
{
    public function execute(array $data): AssetDepreciationRun
    {
        return DB::transaction(function () use ($data) {
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
                    'period_start' => 'A depreciation run already exists for an overlapping period.',
                ]);
            }

            $run = AssetDepreciationRun::create([
                'fiscal_year_id' => $data['fiscal_year_id'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'status' => 'calculated',
                'created_by' => Auth::id(),
            ]);

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

                // Bulan terakhir menghabiskan sisa pembulatan (tidak menyisakan rupiah).
                $isLastMonth = $maxDepreciable - $monthlyAmount < $monthlyAmount;
                $amount = round($isLastMonth ? $maxDepreciable : min($monthlyAmount, $maxDepreciable));

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
}
