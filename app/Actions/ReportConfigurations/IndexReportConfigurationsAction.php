<?php

namespace App\Actions\ReportConfigurations;

use App\Actions\Concerns\InteractsWithIndexRequest;
use App\Domain\ReportConfigurations\ReportConfigurationFilterService;
use App\Http\Requests\ReportConfigurations\IndexReportConfigurationRequest;
use App\Models\ReportConfiguration;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class IndexReportConfigurationsAction
{
    use InteractsWithIndexRequest;

    public function __construct(private ReportConfigurationFilterService $filterService) {}

    public function execute(IndexReportConfigurationRequest $request): LengthAwarePaginator
    {
        $query = ReportConfiguration::query()->with(['creator', 'sections']);

        return $this->handleIndexRequest(
            $request,
            $query,
            $this->filterService,
            ['code', 'name', 'description'],
            ['report_type', 'is_active'],
            'name',
            ['code', 'name', 'report_type', 'is_active', 'created_at'],
        );
    }
}
