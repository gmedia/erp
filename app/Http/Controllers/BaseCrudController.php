<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

abstract class BaseCrudController extends Controller
{
    protected string $modelClass;
    protected string $resourceClass;
    protected string $collectionClass;
    protected string $exportClass;
    protected string $exportRequestClass;
    protected array $allowedSorts = ['id', 'name', 'created_at', 'updated_at'];
    protected array $searchFields = ['name'];

    /**
     * Get the model class for this controller
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the resource class for this controller
     */
    abstract protected function getResourceClass(): string;

    /**
     * Get the collection class for this controller
     */
    abstract protected function getCollectionClass(): string;

    /**
     * Get the export class for this controller
     */
    abstract protected function getExportClass(): string;

    /**
     * Get the export request class for this controller
     */
    abstract protected function getExportRequestClass(): string;

    /**
     * Get allowed sort fields
     */
    protected function getAllowedSorts(): array
    {
        return $this->allowedSorts;
    }

    /**
     * Get search fields
     */
    protected function getSearchFields(): array
    {
        return $this->searchFields;
    }

    /**
     * Apply search filters to query
     */
    protected function applySearch($query, Request $request)
    {
        if ($request->filled('search')) {
            $search = $request->get('search');
            $searchFields = $this->getSearchFields();

            $query->where(function ($q) use ($search, $searchFields) {
                foreach ($searchFields as $field) {
                    $q->orWhere($field, 'like', "%{$search}%");
                }
            });
        }
    }

    /**
     * Apply sorting to query
     */
    protected function applySorting($query, Request $request)
    {
        $allowedSorts = $this->getAllowedSorts();

        $request->validate([
            'sort_by' => ['sometimes', 'in:' . implode(',', $allowedSorts)],
            'sort_direction' => ['sometimes', 'in:asc,desc'],
        ]);

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = strtolower($request->get('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }
    }

    /**
     * Display a listing of the resource with optional search and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $page = $request->get('page', 1);

        $modelClass = $this->getModelClass();
        $collectionClass = $this->getCollectionClass();

        $query = $modelClass::query();

        $this->applySearch($query, $request);
        $this->applySorting($query, $request);

        $items = $query->paginate($perPage, ['*'], 'page', $page);

        return (new $collectionClass($items))->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    abstract public function store($request);

    /**
     * Export resources to Excel based on optional filters.
     */
    abstract public function export($request);
}
