<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Models\AssetDepreciationRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VoidDepreciationRunAction
{
    /**
     * Membatalkan run yang baru dihitung (belum diposting). Run void tidak lagi memblokir
     * perhitungan run berikutnya dan tidak ikut dihitung sebagai bulan yang sudah dibebankan.
     */
    public function execute(AssetDepreciationRun $run): AssetDepreciationRun
    {
        return DB::transaction(function () use ($run) {
            // Kunci run terlebih dulu agar dua permintaan batal bersamaan tidak lolos cek status.
            $locked = AssetDepreciationRun::whereKey($run->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'calculated') {
                throw ValidationException::withMessages([
                    'status' => __('asset_depreciation_runs.only_calculated_can_be_voided'),
                ]);
            }

            $locked->update(['status' => 'void']);

            return $locked->fresh();
        });
    }
}
