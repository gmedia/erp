<?php

namespace App\Actions\AssetDashboard;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class GetAssetDashboardDataAction
{
    public function execute(?int $branchId = null): array
    {
        $summaryStats = $this->scopeBranch(Asset::query(), $branchId)
            ->selectRaw('
                COUNT(*) as total_assets,
                SUM(purchase_cost) as total_purchase_cost,
                SUM(book_value) as total_book_value,
                SUM(accumulated_depreciation) as total_accumulated_depreciation
            ')
            ->first();

        $statusCounts = $this->scopeBranch(Asset::query(), $branchId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $statusMapping = [
            'draft' => ['name' => 'Draft', 'color' => '#6B7280'],
            'active' => ['name' => 'Active', 'color' => '#10B981'],
            'maintenance' => ['name' => 'Maintenance', 'color' => '#F59E0B'],
            'disposed' => ['name' => 'Disposed', 'color' => '#EF4444'],
            'lost' => ['name' => 'Lost', 'color' => '#DC2626'],
        ];

        $statusDistribution = [];
        foreach ($statusMapping as $statusCode => $config) {
            $statusDistribution[] = [
                'id' => $statusCode,
                'name' => $config['name'],
                'color' => $config['color'],
                'count' => $statusCounts->get($statusCode, 0),
            ];
        }

        $categoryDistribution = $this->scopeBranch(Asset::query(), $branchId)
            ->join('asset_categories', 'assets.asset_category_id', '=', 'asset_categories.id')
            ->select('asset_categories.name', DB::raw('count(*) as count'))
            ->groupBy('asset_categories.id', 'asset_categories.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $conditionCounts = $this->scopeBranch(Asset::query(), $branchId)
            ->select('condition', DB::raw('count(*) as count'))
            ->whereNotNull('condition')
            ->groupBy('condition')
            ->pluck('count', 'condition');

        $conditionMapping = [
            'good' => ['name' => 'Good', 'color' => '#10B981'],
            'needs_repair' => ['name' => 'Needs Repair', 'color' => '#F59E0B'],
            'damaged' => ['name' => 'Damaged', 'color' => '#EF4444'],
        ];

        $conditionOverview = [];
        foreach ($conditionMapping as $conditionCode => $config) {
            $conditionOverview[] = [
                'id' => $conditionCode,
                'name' => $config['name'],
                'color' => $config['color'],
                'count' => $conditionCounts->get($conditionCode, 0),
            ];
        }

        $recentMaintenancesQuery = AssetMaintenance::with(['asset' => function ($query) {
            $query->select('id', 'name', 'asset_code', 'branch_id');
        }])
            ->whereIn('status', ['scheduled', 'in_progress']);

        if ($branchId !== null) {
            $recentMaintenancesQuery->whereHas('asset', function (Builder $query) use ($branchId): void {
                $query->where('branch_id', $branchId);
            });
        }

        $recentMaintenances = $recentMaintenancesQuery
            ->orderBy('scheduled_at', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($maintenance) {
                return [
                    'id' => $maintenance->id,
                    'asset_name' => $maintenance->asset->name,
                    'asset_code' => $maintenance->asset->asset_code,
                    'maintenance_type' => $maintenance->maintenance_type,
                    'status' => $maintenance->status,
                    'scheduled_at' => $maintenance->scheduled_at ? $maintenance->scheduled_at->toISOString() : null,
                ];
            });

        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        $warrantyAlerts = $this->scopeBranch(Asset::query(), $branchId)
            ->select('id', 'asset_code', 'name', 'warranty_end_date', 'status')
            ->where('status', 'active')
            ->whereNotNull('warranty_end_date')
            ->where('warranty_end_date', '>=', Carbon::now()->toDateString())
            ->where('warranty_end_date', '<=', $thirtyDaysFromNow->toDateString())
            ->orderBy('warranty_end_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'asset_code' => $asset->asset_code,
                    'name' => $asset->name,
                    'warranty_end_date' => Carbon::parse($asset->warranty_end_date)->toISOString(),
                    'days_remaining' => (int) Carbon::now()
                        ->startOfDay()
                        ->diffInDays(
                            Carbon::parse($asset->warranty_end_date)
                                ->startOfDay(),
                        ),
                ];
            });

        return [
            'summary' => [
                'total_assets' => $summaryStats->total_assets ?? 0,
                'total_purchase_cost' => (float) ($summaryStats->total_purchase_cost ?? 0),
                'total_book_value' => (float) ($summaryStats->total_book_value ?? 0),
                'total_accumulated_depreciation' => (float) ($summaryStats->total_accumulated_depreciation ?? 0),
            ],
            'status_distribution' => $statusDistribution,
            'category_distribution' => $categoryDistribution,
            'condition_overview' => $conditionOverview,
            'recent_maintenances' => $recentMaintenances,
            'warranty_alerts' => $warrantyAlerts,
        ];
    }

    /**
     * @param  Builder<Asset>  $query
     * @return Builder<Asset>
     */
    private function scopeBranch(Builder $query, ?int $branchId): Builder
    {
        if ($branchId !== null) {
            $query->where('assets.branch_id', $branchId);
        }

        return $query;
    }
}
