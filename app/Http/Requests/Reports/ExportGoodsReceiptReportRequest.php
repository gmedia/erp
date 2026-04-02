<?php

namespace App\Http\Requests\Reports;

class ExportGoodsReceiptReportRequest extends AbstractGoodsReceiptReportRequest
{
    public function rules(): array
    {
        return array_merge(
            $this->goodsReceiptRules(),
            $this->exportFormatRules(),
        );
    }
}
