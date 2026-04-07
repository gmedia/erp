'use client';

import {
    createEmptyReportFilters,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    stockMovementReportColumns,
    type StockMovementReportItem,
} from '@/components/reports/stock-movement/Columns';
import { createStockMovementReportFilterFields } from '@/components/reports/stock-movement/Filters';

export default function StockMovementReportPage() {
    return (
        <ReportDataTablePage<StockMovementReportItem>
            title="Stock Movement Report"
            breadcrumbs={[
                { title: 'Reports', href: '#' },
                { title: 'Stock Movement', href: '/reports/stock-movement' },
            ]}
            columns={stockMovementReportColumns}
            filterFields={createStockMovementReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'product_id',
                'warehouse_id',
                'branch_id',
                'category_id',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/stock-movement"
            queryKey={['stock-movement-report']}
            entityName="Stock Movement Report"
            exportEndpoint="/api/reports/stock-movement/export"
        />
    );
}
