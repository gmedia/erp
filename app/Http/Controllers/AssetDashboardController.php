<?php

namespace App\Http\Controllers;

use App\Actions\AssetDashboard\GetAssetDashboardDataAction;
use App\Http\Controllers\Concerns\ResolvesBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetDashboardController extends Controller
{
    use ResolvesBranchScope;

    public function getData(Request $request, GetAssetDashboardDataAction $action): JsonResponse
    {
        $branchId = $this->resolveBranchFromRequest($request);

        $data = $action->execute($branchId);

        return response()->json($data);
    }
}
