<?php

namespace App\Http\Resources\ReportConfigurations;

use App\Models\ReportConfiguration;
use App\Models\ReportSection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property ReportConfiguration $resource */
class ReportConfigurationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'code' => $this->resource->code,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'report_type' => $this->resource->report_type,
            'layout_config' => $this->resource->layout_config,
            'is_active' => $this->resource->is_active,
            'sections' => $this->whenLoaded('sections', fn () => $this->resource->sections
                ->map(fn (ReportSection $section): array => [
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
                    'is_active' => $section->is_active,
                ])->values()->all()),
            'created_by' => $this->whenLoaded('creator', fn () => [
                'id' => $this->resource->created_by,
                'name' => $this->resource->creator?->name,
            ]),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
