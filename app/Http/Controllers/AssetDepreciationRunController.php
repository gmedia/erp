<?php

namespace App\Http\Controllers;

use App\Actions\AssetDepreciationRuns\CalculateDepreciationAction;
use App\Actions\AssetDepreciationRuns\IndexAssetDepreciationRunsAction;
use App\Actions\AssetDepreciationRuns\PostDepreciationToJournalAction;
use App\Actions\AssetDepreciationRuns\IndexAssetDepreciationLinesAction;
use App\Http\Requests\AssetDepreciationRuns\CalculateDepreciationRequest;
use App\Http\Requests\AssetDepreciationRuns\IndexAssetDepreciationRunRequest;
use App\Http\Resources\AssetDepreciationRuns\AssetDepreciationRunCollection;
use App\Http\Resources\AssetDepreciationRuns\AssetDepreciationRunResource;
use App\Http\Resources\AssetDepreciationRuns\AssetDepreciationLineCollection;
use App\Models\AssetDepreciationRun;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AssetDepreciationRunController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('asset-depreciation-runs/index');
    }

    public function apiIndex(IndexAssetDepreciationRunRequest $request, IndexAssetDepreciationRunsAction $action): JsonResponse
    {
        $runs = $action->execute($request);
        return (new AssetDepreciationRunCollection($runs))->response();
    }

    public function calculate(CalculateDepreciationRequest $request, CalculateDepreciationAction $action): JsonResponse
    {
        $run = $action->execute($request->validated());

        return response()->json([
            'message' => 'Depreciation calculated successfully.',
            'run' => new AssetDepreciationRunResource($run),
        ]);
    }

    public function lines(AssetDepreciationRun $assetDepreciationRun, IndexAssetDepreciationLinesAction $action): JsonResponse
    {
        $lines = $action->execute($assetDepreciationRun);

        return (new AssetDepreciationLineCollection($lines))->response();
    }

    public function postToJournal(AssetDepreciationRun $assetDepreciationRun, PostDepreciationToJournalAction $action): JsonResponse
    {
        $action->execute($assetDepreciationRun);

        return response()->json([
            'message' => 'Depreciation successfully posted to journal.',
            'run' => new AssetDepreciationRunResource($assetDepreciationRun->fresh()),
        ]);
    }
}
