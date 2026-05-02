<?php

namespace App\Http\Requests\Reports;

class IndexCustomerStatementReportRequest extends AbstractCustomerStatementReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->customerStatementRules(),
            $this->indexPaginationRules(),
        );
    }
}