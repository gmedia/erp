<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\GoodsReceiptReportExport;

class ExportGoodsReceiptReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'goods_receipt_report';
    }

    protected function makeExport(array $filters): object
    {
        return new GoodsReceiptReportExport($filters);
    }
}
