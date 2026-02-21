<?php

namespace App\Actions\AssetDepreciationRuns;

use App\Models\AssetDepreciationRun;
use Illuminate\Support\Collection;

class IndexAssetDepreciationLinesAction
{
    public function execute(AssetDepreciationRun $run): Collection
    {
        return $run->lines()->with('asset')->get();
    }
}
