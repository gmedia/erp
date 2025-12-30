<?php

namespace App\Http\Controllers;

use App\Exports\DepartmentExport;
use App\Http\Requests\ExportDepartmentRequest;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Http\Resources\DepartmentCollection;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends BaseCrudController
{
    /**
     * Get the model class for this controller
     */
    protected function getModelClass(): string
    {
        return Department::class;
    }

    /**
     * Get the resource class for this controller
     */
    protected function getResourceClass(): string
    {
        return DepartmentResource::class;
    }

    /**
     * Get the collection class for this controller
     */
    protected function getCollectionClass(): string
    {
        return DepartmentCollection::class;
    }

    /**
     * Get the export class for this controller
     */
    protected function getExportClass(): string
    {
        return DepartmentExport::class;
    }

    /**
     * Get the export request class for this controller
     */
    protected function getExportRequestClass(): string
    {
        return ExportDepartmentRequest::class;
    }

    /**
     * Display the specified department.
     */
    public function show(Department $department)
    {
        return (new DepartmentResource($department))->response();
    }

    /**
     * Update the specified department in storage.
     */
    public function update(UpdateDepartmentRequest $request, Department $department)
    {
        $department->update($request->validated());
        return (new DepartmentResource($department))->response();
    }

    /**
     * Remove the specified department from storage.
     */
    public function destroy(Department $department)
    {
        $department->delete();
        return response()->json(null, 204);
    }
}
