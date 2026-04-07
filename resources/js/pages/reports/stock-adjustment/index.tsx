'use client';

import {
    createEmptyReportFilters,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    stockAdjustmentReportColumns,
    type StockAdjustmentReportItem,
} from '@/components/reports/stock-adjustment/Columns';
import { createStockAdjustmentReportFilterFields } from '@/components/reports/stock-adjustment/Filters';

export default function StockAdjustmentReportPage() {
    return (
        <ReportDataTablePage<StockAdjustmentReportItem>
            title="Stock Adjustment Report"
            breadcrumbs={[
                { title: 'Reports', href: '#' },
                {
                    title: 'Stock Adjustment',
                    href: '/reports/stock-adjustment',
                },
            ]}
            columns={stockAdjustmentReportColumns}
            filterFields={createStockAdjustmentReportFilterFields()}
            initialFilters={createEmptyReportFilters([
                'warehouse_id',
                'branch_id',
                'adjustment_type',
                'status',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/stock-adjustment"
            queryKey={['stock-adjustment-report']}
            entityName="Stock Adjustment Report"
            exportEndpoint="/api/reports/stock-adjustment/export"
        />
    );
}
