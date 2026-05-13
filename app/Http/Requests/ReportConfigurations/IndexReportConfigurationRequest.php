<?php

namespace App\Http\Requests\ReportConfigurations;

use App\Http\Requests\BaseListingRequest;
use App\Models\ReportConfiguration;
use Illuminate\Validation\Rule;

class IndexReportConfigurationRequest extends BaseListingRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
                'report_type' => ['nullable', Rule::in(ReportConfiguration::TYPES)],
                'is_active' => ['nullable', 'boolean'],
            ],
            $this->listingSortRules('code,name,report_type,is_active,created_at'),
            $this->paginationRules(),
        );
    }
}
