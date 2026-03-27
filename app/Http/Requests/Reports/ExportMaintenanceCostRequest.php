<?php

namespace App\Http\Requests\Reports;

use Illuminate\Validation\Rule;

class ExportMaintenanceCostRequest extends AbstractReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->searchRules(),
            [
            'asset_category_id' => ['nullable', 'integer', 'exists:asset_categories,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'maintenance_type' => [
                'nullable',
                'string',
                Rule::in(['preventive', 'corrective', 'calibration', 'other']),
            ],
            ],
            $this->dateRangeRules(),
            $this->sortByEnumRules([
                'maintenance_type',
                'status',
                'scheduled_at',
                'performed_at',
                'cost',
                'asset_code',
                'asset_name',
                'supplier_name',
            ]),
            $this->sortDirectionRules(),
            $this->exportFormatRules(),
        );
    }
}
