<?php

namespace App\Http\Requests\Reports;

class IndexMaintenanceCostRequest extends AbstractMaintenanceCostRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->maintenanceCostRules(),
            $this->indexLimitRules(),
        );
    }
}
