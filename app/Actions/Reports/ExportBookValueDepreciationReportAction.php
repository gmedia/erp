<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\ExportBookValueDepreciationRequest;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class ExportBookValueDepreciationReportAction
{
    public function execute(ExportBookValueDepreciationRequest $request): JsonResponse
    {
        $query = Asset::query()
            ->with(['category', 'branch'])
            ->whereIn('status', ['active', 'maintenance', 'disposed', 'lost']);

        if ($request->filled('search')) {
            $query->where(function (Builder $q) use ($request) {
                $q->where('asset_code', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('asset_category_id')) {
            $query->where('asset_category_id', $request->asset_category_id);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        $sortBy = $request->get('sort_by', 'asset_code');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortBy, $sortDirection);

        $assets = $query->get();

        $csvData = [];
        $csvData[] = [
            'Asset Code',
            'Name',
            'Category',
            'Branch',
            'Purchase Date',
            'Purchase Cost',
            'Salvage Value',
            'Useful Life (Months)',
            'Accumulated Depreciation',
            'Book Value',
        ];

        foreach ($assets as $asset) {
            $csvData[] = [
                $asset->asset_code,
                $asset->name,
                $asset->category?->name ?? '-',
                $asset->branch?->name ?? '-',
                $asset->purchase_date?->format('Y-m-d') ?? '-',
                $asset->purchase_cost,
                $asset->salvage_value,
                $asset->useful_life_months,
                $asset->accumulated_depreciation,
                $asset->book_value,
            ];
        }

        return response()->json([
            'data' => $csvData,
            'filename' => 'book_value_depreciation_report_' . date('Y-m-d_H-i-s') . '.csv',
        ]);
    }
}
