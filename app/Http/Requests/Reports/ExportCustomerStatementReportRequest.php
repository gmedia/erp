<?php

namespace App\Http\Requests\Reports;

class ExportCustomerStatementReportRequest extends AbstractCustomerStatementReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->customerStatementRules(),
            $this->exportFormatRules(),
        );
    }
}