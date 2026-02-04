<?php

namespace App\Actions\AssetCategories;

use App\Actions\Concerns\SimpleCrudIndexAction;
use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;

class IndexAssetCategoriesAction extends SimpleCrudIndexAction
{
    protected function getModelClass(): string
    {
        return AssetCategory::class;
    }

    public function execute(FormRequest $request): \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        }

        $this->applySorting(
            $query,
            $request->get('sort_by', $this->getDefaultSortBy()),
            strtolower($request->get('sort_direction', $this->getDefaultSortDirection())) === 'asc' ? 'asc' : 'desc',
            $this->getSortableFields()
        );

        return $query->paginate($request->get('per_page', $this->getDefaultPerPage()));
    }

    protected function getSortableFields(): array
    {
        return ['id', 'name', 'code', 'useful_life_months_default', 'created_at', 'updated_at'];
    }
}
