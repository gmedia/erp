'use client';

import {
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    bookValueDepreciationColumns,
    BookValueDepreciationReportItem,
} from '@/components/reports/book-value-depreciation/Columns';
import { createBookValueReportFilterFields } from '@/components/reports/book-value-depreciation/Filters';

export default function BookValueDepreciationReport() {
    return (
        <ReportDataTablePage<BookValueDepreciationReportItem>
            title="Book Value & Depreciation Report"
            breadcrumbs={createReportBreadcrumbs(
                'Book Value & Depreciation',
                '/reports/book-value-depreciation',
            )}
            columns={bookValueDepreciationColumns}
            filterFields={createBookValueReportFilterFields()}
            initialFilters={{
                search: '',
                asset_category_id: '',
                branch_id: '',
            }}
            endpoint="/api/reports/book-value-depreciation"
            queryKey={['book-value-report']}
            entityName="Asset (Book Value)"
            exportEndpoint="/api/reports/book-value-depreciation/export"
            defaultColumnVisibility={{
                branch_name: false,
                salvage_value: false,
                useful_life_months: false,
                category_name: false,
            }}
        />
    );
}
