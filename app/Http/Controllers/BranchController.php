<?php

namespace App\Http\Controllers;

use App\Actions\Branches\ExportBranchesAction;
use App\Actions\Branches\IndexBranchesAction;
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
     */
    public function index(IndexBranchRequest $request): JsonResponse
    {
        $branches = (new IndexBranchesAction())->execute($request);

        return (new BranchCollection($branches))->response();
    }

    /**
     * Store a newly created branch in storage.
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
     */
    public function show(Branch $branch): JsonResponse
    {
        return (new BranchResource($branch))->response();
    }

    /**
     * Update the specified branch in storage.
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        $branch->update($request->validated());

        return (new BranchResource($branch))->response();
    }

    /**
     * Remove the specified branch from storage.
     */
    public function destroy(Branch $branch): JsonResponse
    {
        $branch->delete();

        return response()->json(null, 204);
    }

    /**
     * Export branches to Excel based on filters.
     */
    public function export(ExportBranchRequest $request): JsonResponse
    {
        return (new ExportBranchesAction())->execute($request);
    }
}
