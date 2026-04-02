<?php

namespace App\Http\Requests\Reports;

class ExportMaintenanceCostRequest extends AbstractMaintenanceCostRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->maintenanceCostRules(),
            $this->exportFormatRules(),
        );
    }
}
