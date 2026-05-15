<?php

namespace App\Http\Controllers;

use App\Actions\ReportConfigurations\ExportReportConfigurationsAction;
use App\Actions\ReportConfigurations\IndexReportConfigurationsAction;
use App\Http\Controllers\Concerns\StoresItemsInTransaction;
use App\Http\Requests\ReportConfigurations\ExportReportConfigurationRequest;
use App\Http\Requests\ReportConfigurations\IndexReportConfigurationRequest;
use App\Http\Requests\ReportConfigurations\StoreReportConfigurationRequest;
use App\Http\Requests\ReportConfigurations\UpdateReportConfigurationRequest;
use App\Http\Resources\ReportConfigurations\ReportConfigurationCollection;
use App\Http\Resources\ReportConfigurations\ReportConfigurationResource;
use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Illuminate\Http\JsonResponse;

class ReportConfigurationController extends Controller
{
    use StoresItemsInTransaction;

    public function index(IndexReportConfigurationRequest $request, IndexReportConfigurationsAction $action): JsonResponse
    {
        return (new ReportConfigurationCollection($action->execute($request)))->response();
    }

    public function store(StoreReportConfigurationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $sections = $data['sections'] ?? [];
        unset($data['sections']);
        $data['created_by'] = auth()->id();

        $config = $this->storeWithSyncedItems(
            $data,
            $sections,
            fn (array $attributes): ReportConfiguration => ReportConfiguration::create($attributes),
            fn (ReportConfiguration $config): null => null,
            fn (ReportConfiguration $config, array $items): null => $this->syncSections($config, $items),
        );

        return (new ReportConfigurationResource($config->load(['creator', 'sections'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ReportConfiguration $reportConfiguration): JsonResponse
    {
        return (new ReportConfigurationResource($reportConfiguration->load(['creator', 'sections'])))->response();
    }

    public function update(UpdateReportConfigurationRequest $request, ReportConfiguration $reportConfiguration): JsonResponse
    {
        $data = $request->validated();
        $sections = $data['sections'] ?? null;
        unset($data['sections']);

        $this->updateWithSyncedItems(
            $reportConfiguration,
            $data,
            $sections,
            fn (array $attributes): array => $attributes,
            fn (ReportConfiguration $config, array $items): null => $this->syncSections($config, $items),
        );

        return (new ReportConfigurationResource($reportConfiguration->refresh()->load(['creator', 'sections'])))->response();
    }

    public function destroy(ReportConfiguration $reportConfiguration): JsonResponse
    {
        return $this->destroyModel($reportConfiguration);
    }

    public function export(ExportReportConfigurationRequest $request, ExportReportConfigurationsAction $action): JsonResponse
    {
        return $action->execute($request);
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function syncSections(ReportConfiguration $config, array $items): null
    {
        $config->sections()->delete();

        $codeToId = [];

        foreach ($items as $index => $item) {
            $attributes = [
                'code' => $item['code'],
                'name' => $item['name'],
                'sort_order' => $item['sort_order'] ?? ($index + 1) * 10,
                'section_type' => $item['section_type'],
                'account_type_filter' => $item['account_type_filter'] ?? null,
                'account_sub_type_filter' => $item['account_sub_type_filter'] ?? null,
                'sign_convention' => $item['sign_convention'] ?? ReportSection::SIGN_NORMAL,
                'formula' => $item['formula'] ?? null,
                'is_active' => $item['is_active'] ?? true,
                'parent_id' => null,
            ];

            $parentCode = $item['parent_code'] ?? null;
            if ($parentCode !== null && isset($codeToId[$parentCode])) {
                $attributes['parent_id'] = $codeToId[$parentCode];
            }

            /** @var ReportSection $section */
            $section = $config->sections()->create($attributes);
            $codeToId[$item['code']] = $section->id;
        }

        return null;
    }
}
