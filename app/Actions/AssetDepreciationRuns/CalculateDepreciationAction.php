<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Models\Asset;
use App\Models\AssetDepreciationLine;
use App\Models\AssetDepreciationRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CalculateDepreciationAction
{
    public function execute(array $data): AssetDepreciationRun
    {
        return DB::transaction(function () use ($data) {
            // Check if run already exists for this period
            $existingRun = AssetDepreciationRun::where('fiscal_year_id', $data['fiscal_year_id'])
                ->where('period_start', $data['period_start'])
                ->where('period_end', $data['period_end'])
                ->whereIn('status', ['draft', 'calculated', 'posted'])
                ->first();

            if ($existingRun) {
                throw ValidationException::withMessages([
                    'period_start' => 'A depreciation run already exists for this period.'
                ]);
            }

            $run = AssetDepreciationRun::create([
                'fiscal_year_id' => $data['fiscal_year_id'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'status' => 'calculated',
                'created_by' => \Illuminate\Support\Facades\Auth::id(),
            ]);

            // Find eligible assets
            $assets = Asset::whereIn('status', ['active', 'maintenance'])
                ->whereNotNull('depreciation_method')
                ->where('depreciation_start_date', '<=', $data['period_end'])
                ->where('purchase_cost', '>', DB::raw('COALESCE(salvage_value, 0)'))
                ->where('useful_life_months', '>', 0)
                ->where(function ($q) {
                    $q->whereNull('book_value')
                      ->orWhereRaw('book_value > COALESCE(salvage_value, 0)');
                })
                ->get();

            $lines = [];

            foreach ($assets as $asset) {
                $cost = (float) $asset->purchase_cost;
                $salvage = (float) $asset->salvage_value;
                $usefulLife = (int) $asset->useful_life_months;

                $monthlyAmount = round(($cost - $salvage) / $usefulLife, 2);

                $accumulatedBefore = (float) $asset->accumulated_depreciation;
                $bookValueBefore = (float) ($asset->book_value ?? $cost);

                // Adjust if amount exceeds remaining depreciable value
                $maxDepreciable = $bookValueBefore - $salvage;
                
                if ($maxDepreciable <= 0) {
                    continue; // Already fully depreciated
                }

                $amount = min($monthlyAmount, $maxDepreciable);

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

            if (!empty($lines)) {
                AssetDepreciationLine::insert($lines);
            }

            return $run->loadCount('lines');
        });
    }
}
