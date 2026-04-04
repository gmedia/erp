<?php

namespace App\Actions\Reports;

use App\Actions\Reports\Concerns\ConfiguredReportExportAction;
use App\Exports\BookValueDepreciationExport;

class ExportBookValueDepreciationReportAction extends ConfiguredReportExportAction
{
    protected function filenamePrefix(): string
    {
        return 'book_value_depreciation_report';
    }

    protected function supportsCsvExport(): bool
    {
        return false;
    }

    protected function makeExport(array $filters): object
    {
        return new BookValueDepreciationExport($filters);
    }
}
