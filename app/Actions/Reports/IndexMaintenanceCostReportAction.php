<?php

namespace App\Actions\Reports;

use App\Http\Requests\Reports\IndexMaintenanceCostRequest;
use App\Models\AssetMaintenance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class IndexMaintenanceCostReportAction
{
    public function execute(IndexMaintenanceCostRequest $request): LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
    {
        $query = AssetMaintenance::query()
            ->with(['asset.category', 'asset.branch', 'supplier']);

        if ($request->filled('start_date')) {
            $query->whereDate('performed_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('performed_at', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $query->where(function (Builder $q) use ($request) {
                $q->whereHas('asset', function (Builder $sq) use ($request) {
                    $sq->where('asset_code', 'like', '%' . $request->search . '%')
                       ->orWhere('name', 'like', '%' . $request->search . '%');
                })->orWhere('notes', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('asset_category_id')) {
            $query->whereHas('asset', function (Builder $q) use ($request) {
                $q->where('asset_category_id', $request->asset_category_id);
            });
        }

        if ($request->filled('branch_id')) {
            $query->whereHas('asset', function (Builder $q) use ($request) {
                $q->where('branch_id', $request->branch_id);
            });
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('maintenance_type')) {
            $query->where('maintenance_type', $request->maintenance_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->get('sort_by', 'performed_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if (in_array($sortBy, ['asset_code', 'asset_name'])) {
            $query->join('assets', 'asset_maintenances.asset_id', '=', 'assets.id')
                  ->orderBy($sortBy === 'asset_name' ? 'assets.name' : 'assets.asset_code', $sortDirection)
                  ->select('asset_maintenances.*');
        } elseif ($sortBy === 'supplier_name') {
            $query->leftJoin('suppliers', 'asset_maintenances.supplier_id', '=', 'suppliers.id')
                  ->orderBy('suppliers.name', $sortDirection)
                  ->select('asset_maintenances.*');
        } else {
            $query->orderBy('asset_maintenances.' . $sortBy, $sortDirection);
        }

        if ($request->boolean('export')) {
             return $query->get();
        }

        $perPage = $request->get('per_page', 15);
        
        return $query->paginate($perPage)->withQueryString();
    }
}
