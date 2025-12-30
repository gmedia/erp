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
    public function store(Request $request)
    {
        $modelClass = $this->getModelClass();
        $resourceClass = $this->getResourceClass();

        // Use all() if validated() doesn't exist (for basic Request)
        $data = method_exists($request, 'validated') ? $request->validated() : $request->all();

        $item = $modelClass::create($data);

        return (new $resourceClass($item))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }



    /**
     * Export resources to Excel based on optional filters.
     */
    public function export(Request $request)
    {
        $exportRequestClass = $this->getExportRequestClass();
        $exportClass = $this->getExportClass();

        $validated = method_exists($request, 'validated') ? $request->validated() : $request->all();

        $filters = [
            'sort_by' => $validated['sort_by'] ?? null,
            'sort_direction' => $validated['sort_direction'] ?? null,
        ];

        // Add search filter if it exists in validated data (used by frontend)
        if (isset($validated['search'])) {
            $filters['search'] = $validated['search'];
        }

        // Add name filter if it exists in validated data (fallback for direct API calls)
        if (isset($validated['name'])) {
            $filters['name'] = $validated['name'];
        }

        // Remove null values
        $filters = array_filter($filters);

        // Generate filename
        $entityName = strtolower(str_replace('Controller', '', class_basename($this)));
        $filename = $entityName . '_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filePath = 'exports/' . $filename;

        // Create export and store on public disk
        $export = new $exportClass($filters);
        Excel::store($export, $filePath, 'public');

        // Generate public URL
        $url = Storage::url($filePath);

        return response()->json([
            'url' => $url,
            'filename' => $filename,
        ]);
    }
}
