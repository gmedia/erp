<?php

namespace App\Http\Controllers;

use App\Actions\AssetDashboard\GetAssetDashboardDataAction;
use Illuminate\Http\JsonResponse;

class AssetDashboardController extends Controller
{
    /**
     * Get aggregate data for the asset dashboard.
     */
    public function getData(GetAssetDashboardDataAction $action): JsonResponse
    {
        $data = $action->execute();

        return response()->json($data);
    }
}
