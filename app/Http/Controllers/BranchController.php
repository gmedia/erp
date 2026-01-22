<?php

namespace App\Http\Controllers;

use App\Actions\Branches\ExportBranchesAction;
use App\Actions\Branches\IndexBranchesAction;
use App\Domain\Branches\BranchFilterService;
use App\Http\Requests\Branches\ExportBranchRequest;
use App\Http\Requests\Branches\IndexBranchRequest;
use App\Http\Requests\Branches\StoreBranchRequest;
use App\Http\Requests\Branches\UpdateBranchRequest;
use App\Http\Resources\Branches\BranchCollection;
use App\Http\Resources\Branches\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;

/**
 * Controller for branch management operations.
 *
 * Handles CRUD operations and export functionality for branches.
 */
class BranchController extends Controller
{
    /**
     * Display a listing of the branches.
     *
     * Supports pagination, search filtering, and sorting.
     *
     * @param  \App\Http\Requests\Branches\IndexBranchRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(IndexBranchRequest $request): JsonResponse
    {
        $branches = (new IndexBranchesAction(app(BranchFilterService::class)))->execute($request);

        return (new BranchCollection($branches))->response();
    }

    /**
     * Store a newly created branch in storage.
     *
     * @param  \App\Http\Requests\Branches\StoreBranchRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $branch = Branch::create($request->validated());

        return (new BranchResource($branch))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified branch.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Branch $branch): JsonResponse
    {
        return (new BranchResource($branch))->response();
    }

    /**
     * Update the specified branch in storage.
     *
     * @param  \App\Http\Requests\Branches\UpdateBranchRequest  $request
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $branch->update($request->validated());

        return (new BranchResource($branch))->response();
    }

    /**
     * Remove the specified branch from storage.
     *
     * @param  \App\Models\Branch  $branch
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $branch->delete();

        return response()->json(null, 204);
    }

    /**
     * Export branches to Excel based on filters.
     *
     * @param  \App\Http\Requests\Branches\ExportBranchRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(ExportBranchRequest $request): JsonResponse
    {
        return (new ExportBranchesAction(app(BranchFilterService::class)))->execute($request);
    }
}
