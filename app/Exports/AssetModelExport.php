<?php

namespace App\Exports;

use App\Models\AssetModel;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetModelExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    protected $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    public function query(): Builder
    {
        $query = AssetModel::query()->with(['category']);

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('model_name', 'like', "%{$search}%")
                    ->orWhere('manufacturer', 'like', "%{$search}%");
            });
        }

        if (!empty($this->filters['asset_category_id'])) {
            $query->where('asset_category_id', $this->filters['asset_category_id']);
        }

        $sortBy = $this->filters['sort_by'] ?? 'created_at';
        $sortDirection = $this->filters['sort_direction'] ?? 'desc';
        $allowedSortColumns = ['model_name', 'manufacturer', 'asset_category_id', 'created_at'];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        return $query;
    }

    public function headings(): array
    {
        return ['ID', 'Model Name', 'Manufacturer', 'Category', 'Specs', 'Created At'];
    }

    public function map($assetModel): array
    {
        return [
            $assetModel->id,
            $assetModel->model_name,
            $assetModel->manufacturer,
            $assetModel->category?->name,
            $assetModel->specs ? json_encode($assetModel->specs) : '',
            $assetModel->created_at?->toIso8601String(),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
