<?php

namespace App\Actions\AssetDashboard;

use App\Models\Asset;
use App\Models\AssetMaintenance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetAssetDashboardDataAction
{
    public function execute(): array
    {
        // 1. Summary Totals
        // Use single query to get counts and sums
        $summaryStats = Asset::query()
            ->selectRaw('
                COUNT(*) as total_assets,
                SUM(purchase_cost) as total_purchase_cost,
                SUM(book_value) as total_book_value,
                SUM(accumulated_depreciation) as total_accumulated_depreciation
            ')
            ->first();

        // 2. Status Distribution (from enum)
        $statusCounts = Asset::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $statusMapping = [
            'draft' => ['name' => 'Draft', 'color' => '#6B7280'],       // Gray
            'active' => ['name' => 'Active', 'color' => '#10B981'],     // Emerald
            'maintenance' => ['name' => 'Maintenance', 'color' => '#F59E0B'], // Amber
            'disposed' => ['name' => 'Disposed', 'color' => '#EF4444'], // Red
            'lost' => ['name' => 'Lost', 'color' => '#DC2626'],         // Darker Red
        ];

        $statusDistribution = [];
        foreach ($statusMapping as $statusCode => $config) {
            $statusDistribution[] = [
                'id' => $statusCode,
                'name' => $config['name'],
                'color' => $config['color'],
                'count' => $statusCounts->has($statusCode) ? $statusCounts->get($statusCode)->count : 0,
            ];
        }

        // 3. Category Distribution
        $categoryDistribution = Asset::query()
            ->join('asset_categories', 'assets.asset_category_id', '=', 'asset_categories.id')
            ->select('asset_categories.name', DB::raw('count(*) as count'))
            ->groupBy('asset_categories.id', 'asset_categories.name')
            ->orderByDesc('count')
            ->limit(10) // Top 10 categories
            ->get();

        // 4. Condition Overview
        $conditionCounts = Asset::query()
            ->select('condition', DB::raw('count(*) as count'))
            ->whereNotNull('condition')
            ->groupBy('condition')
            ->get()
            ->keyBy('condition');
            
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
                'count' => $conditionCounts->has($conditionCode) ? $conditionCounts->get($conditionCode)->count : 0,
            ];
        }

        // 5. Recent Maintenances
        $recentMaintenances = AssetMaintenance::with(['asset' => function ($query) {
                $query->select('id', 'name', 'asset_code');
            }])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->orderBy('scheduled_at', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($maintenance) {
                return [
                    'id' => $maintenance->id,
                    'asset_name' => $maintenance->asset ? $maintenance->asset->name : 'Unknown',
                    'asset_code' => $maintenance->asset ? $maintenance->asset->asset_code : 'Unknown',
                    'maintenance_type' => $maintenance->maintenance_type,
                    'status' => $maintenance->status,
                    'scheduled_at' => $maintenance->scheduled_at ? $maintenance->scheduled_at->toISOString() : null,
                ];
            });

        // 6. Warranty Alerts (Active assets with warranty ending in next 30 days)
        $thirtyDaysFromNow = Carbon::now()->addDays(30);
        $warrantyAlerts = Asset::query()
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
                    'days_remaining' => (int) Carbon::now()->startOfDay()->diffInDays(Carbon::parse($asset->warranty_end_date)->startOfDay()),
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
}
