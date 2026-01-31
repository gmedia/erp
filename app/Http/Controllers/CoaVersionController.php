<?php

namespace App\Http\Controllers;

use App\Actions\CoaVersions\ExportCoaVersionsAction;
use App\Actions\CoaVersions\IndexCoaVersionsAction;
use App\Http\Requests\CoaVersions\ExportCoaVersionRequest;
use App\Http\Requests\CoaVersions\IndexCoaVersionRequest;
use App\Http\Requests\CoaVersions\StoreCoaVersionRequest;
use App\Http\Requests\CoaVersions\UpdateCoaVersionRequest;
use App\Http\Resources\CoaVersions\CoaVersionCollection;
use App\Http\Resources\CoaVersions\CoaVersionResource;
use App\Models\CoaVersion;
use Illuminate\Http\JsonResponse;

/**
 * Controller for COA Version management operations.
 */
class CoaVersionController extends Controller
{
    /**
     * Display a listing of the COA versions.
     */
    public function index(IndexCoaVersionRequest $request, IndexCoaVersionsAction $action): JsonResponse
    {
        $coaVersions = $action->execute($request);

        return (new CoaVersionCollection($coaVersions))->response();
    }

    /**
     * Store a newly created COA version in storage.
     */
    public function store(StoreCoaVersionRequest $request): JsonResponse
    {
        $coaVersion = CoaVersion::create($request->validated());

        return (new CoaVersionResource($coaVersion))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified COA version.
     */
    public function show(CoaVersion $coaVersion): JsonResponse
    {
        return (new CoaVersionResource($coaVersion->load('fiscalYear')))->response();
    }

    /**
     * Update the specified COA version in storage.
     */
    public function update(UpdateCoaVersionRequest $request, CoaVersion $coaVersion): JsonResponse
    {
        $coaVersion->update($request->validated());

        return (new CoaVersionResource($coaVersion->load('fiscalYear')))->response();
    }

    /**
     * Remove the specified COA version from storage.
     */
    public function destroy(CoaVersion $coaVersion): JsonResponse
    {
        $coaVersion->delete();

        return response()->json(null, 204);
    }

    /**
     * Export COA versions to Excel based on filters.
     */
    public function export(ExportCoaVersionRequest $request, ExportCoaVersionsAction $action): JsonResponse
    {
        return $action->execute($request);
    }
}
