<?php

namespace App\Http\Controllers;

use App\Exports\PositionExport;
use App\Http\Requests\ExportPositionRequest;
use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Resources\PositionCollection;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends BaseCrudController
{
    /**
     * Get the model class for this controller
     */
    protected function getModelClass(): string
    {
        return Position::class;
    }

    /**
     * Get the resource class for this controller
     */
    protected function getResourceClass(): string
    {
        return PositionResource::class;
    }

    /**
     * Get the collection class for this controller
     */
    protected function getCollectionClass(): string
    {
        return PositionCollection::class;
    }

    /**
     * Get the export class for this controller
     */
    protected function getExportClass(): string
    {
        return PositionExport::class;
    }

    /**
     * Get the export request class for this controller
     */
    protected function getExportRequestClass(): string
    {
        return ExportPositionRequest::class;
    }

    /**
     * Display the specified position.
     */
    public function show(Position $position)
    {
        return (new PositionResource($position))->response();
    }

    /**
     * Update the specified position in storage.
     */
    public function update(UpdatePositionRequest $request, Position $position)
    {
        $position->update($request->validated());
        return (new PositionResource($position))->response();
    }

    /**
     * Remove the specified position from storage.
     */
    public function destroy(Position $position)
    {
        $position->delete();
        return response()->json(null, 204);
    }
}
