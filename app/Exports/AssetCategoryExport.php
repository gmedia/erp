<?php

namespace App\Exports;

use App\Exports\Concerns\SimpleCrudExport;
use App\Models\AssetCategory;
use Illuminate\Database\Eloquent\Builder;

class AssetCategoryExport extends SimpleCrudExport
{
    protected function getModelClass(): string
    {
        return AssetCategory::class;
    }

    protected function getSortableFields(): array
    {
        return ['id', 'code', 'name', 'useful_life_months_default', 'created_at', 'updated_at'];
    }

    public function query(): Builder
    {
        if ($this->query) {
            return $this->query;
        }

        $modelClass = $this->getModelClass();
        $query = $modelClass::query();

        // Apply search filter if provided
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';

        $allowedSorts = $this->getSortableFields();
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Code',
            'Name',
            'Default Useful Life (Months)',
            'Created At',
            'Updated At',
        ];
    }

    public function map($model): array
    {
        return [
            $model->id,
            $model->code,
            $model->name,
            $model->useful_life_months_default,
            $model->created_at ? $model->created_at->toIso8601String() : null,
            $model->updated_at ? $model->updated_at->toIso8601String() : null,
        ];
    }
}
