<?php

namespace App\Actions\ReportConfigurations;

use App\Models\ReportConfiguration;

class GetReportConfigurationByTypeAction
{
    /**
     * @return array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     description: ?string,
     *     report_type: string,
     *     sections: array<int, array<string, mixed>>,
     * }|null
     */
    public function execute(string $reportType): ?array
    {
        /** @var ReportConfiguration|null $config */
        $config = ReportConfiguration::query()
            ->active()
            ->ofType($reportType)
            ->with([
                'sections' => fn ($query) => $query->where('is_active', true)->orderBy('sort_order'),
            ])
            ->orderBy('id')
            ->first();

        if ($config === null) {
            return null;
        }

        return [
            'id' => $config->id,
            'code' => $config->code,
            'name' => $config->name,
            'description' => $config->description,
            'report_type' => $config->report_type,
            'sections' => $config->sections->map(fn ($section): array => [
                'id' => $section->id,
                'parent_id' => $section->parent_id,
                'code' => $section->code,
                'name' => $section->name,
                'sort_order' => $section->sort_order,
                'section_type' => $section->section_type,
                'account_type_filter' => $section->account_type_filter,
                'account_sub_type_filter' => $section->account_sub_type_filter,
                'sign_convention' => $section->sign_convention,
                'formula' => $section->formula,
            ])->values()->all(),
        ];
    }
}
