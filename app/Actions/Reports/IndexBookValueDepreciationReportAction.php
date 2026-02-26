<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexBookValueDepreciationRequest;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IndexBookValueDepreciationReportAction
{
    public function execute(IndexBookValueDepreciationRequest $request): LengthAwarePaginator
    {
        $query = Asset::query()
            ->with(['category', 'branch'])
            ->whereIn('status', ['active', 'maintenance', 'disposed', 'lost']); // Typically draft are not depreciated

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

        // Apply sorting (with basic validation in request)
        $query->orderBy($sortBy, $sortDirection);

        $perPage = $request->get('per_page', 15);
        
        return $query->paginate($perPage)->withQueryString();
    }
}
