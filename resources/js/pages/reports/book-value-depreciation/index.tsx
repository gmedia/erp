'use client';

import { ReportDataTablePage } from '@/components/common/ReportDataTablePage';
import {
    bookValueDepreciationColumns,
    BookValueDepreciationReportItem,
} from '@/components/reports/book-value-depreciation/Columns';
import { createBookValueReportFilterFields } from '@/components/reports/book-value-depreciation/Filters';

export default function BookValueDepreciationReport() {
    return (
        <ReportDataTablePage<BookValueDepreciationReportItem>
            title="Book Value & Depreciation Report"
            breadcrumbs={[
                { title: 'Reports', href: '#' },
                {
                    title: 'Book Value & Depreciation',
                    href: '/reports/book-value-depreciation',
                },
            ]}
            columns={bookValueDepreciationColumns}
            filterFields={createBookValueReportFilterFields()}
            initialFilters={{
                search: '',
                asset_category_id: '',
                branch_id: '',
            }}
            endpoint="/reports/book-value-depreciation"
            queryKey={['book-value-report']}
            entityName="Asset (Book Value)"
            exportEndpoint="/reports/book-value-depreciation/export"
        />
    );
}
