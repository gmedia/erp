<?php

namespace App\Http\Controllers;

use App\Actions\AssetDashboard\GetAssetDashboardDataAction;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetDashboardController extends Controller
{
    /**
     * Display the Asset Dashboard page.
     */
    public function index(): Response
    {
        return Inertia::render('asset-dashboard/index');
    }

    /**
     * Get aggregate data for the asset dashboard.
     */
    public function getData(GetAssetDashboardDataAction $action): JsonResponse
    {
        $data = $action->execute();

        return response()->json($data);
    }
}
