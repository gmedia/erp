<?php

namespace App\Actions\Assets;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Actions\Reports\Concerns\SumsReportTotals;
use App\Domain\Assets\AssetFilterService;
use App\Http\Requests\Assets\IndexAssetRequest;
use App\Models\Asset;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexAssetsAction
{
    use InteractsWithIndexRequest;
    use SumsReportTotals;

    private const SEARCH_FIELDS = ['name', 'asset_code', 'serial_number', 'barcode'];

    private const PRIMARY_FILTER_KEYS = [
        'asset_category_id',
        'asset_model_id',
        'branch_id',
        'asset_location_id',
        'department_id',
        'employee_id',
        'supplier_id',
        'status',
        'exclude_status',
        'condition',
    ];

    public function __construct(
        private AssetFilterService $filterService
    ) {}

    /**
     * Total nilai perolehan untuk SELURUH hasil filter (bukan hanya halaman aktif).
     *
     * @return array{as_of_date: string, totals: array<string, string>}
     */
    public function summarize(IndexAssetRequest $request): array
    {
        $query = Asset::query();

        if ($request->filled('search')) {
            $this->filterService->applySearch(
                $query,
                $request->string('search')->toString(),
                self::SEARCH_FIELDS,
            );
        } else {
            $this->filterService->applyAdvancedFilters(
                $query,
                $this->primaryFilters($request),
            );
        }

        return $this->buildReportSummary($query, ['purchase_cost']);
    }

    public function execute(IndexAssetRequest $request): LengthAwarePaginator
    {
        $query = Asset::query()->with([
            'category',
            'model',
            'branch',
            'location',
            'department',
            'employee',
            'supplier',
        ]);

        return $this->handleSearchOrPrimaryIndexRequest(
            $request,
            $query,
            $this->filterService,
            self::SEARCH_FIELDS,
            self::PRIMARY_FILTER_KEYS,
            [],
            'created_at',
            [
                'id',
                'asset_code',
                'name',
                'purchase_date',
                'purchase_cost',
                'status',
                'created_at',
                'category',
                'branch',
                'location',
                'department',
                'employee',
                'supplier',
            ],
        );
    }

    /**
     * Sama persis dengan extractRequestFilters() pada jalur daftar agar total selalu cocok.
     *
     * @return array<string, mixed>
     */
    private function primaryFilters(IndexAssetRequest $request): array
    {
        $filters = [];

        foreach (self::PRIMARY_FILTER_KEYS as $filterKey) {
            $filters[$filterKey] = $request->get($filterKey);
        }

        return $filters;
    }
}
