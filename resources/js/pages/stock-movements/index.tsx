'use client';

import {
    createEmptyReportFilters,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    createStockMovementsColumns,
    type StockMovementItem,
} from '@/components/stock-movements/Columns';
import { createStockMovementsFilterFields } from '@/components/stock-movements/Filters';

export default function StockMovementsPage() {
    return (
        <ReportDataTablePage<StockMovementItem>
            title="Stock Movements"
            breadcrumbs={[
                { title: 'Inventory', href: '#' },
                { title: 'Stock Movements', href: '/stock-movements' },
            ]}
            columns={createStockMovementsColumns()}
            filterFields={createStockMovementsFilterFields()}
            initialFilters={createEmptyReportFilters([
                'product_id',
                'warehouse_id',
                'movement_type',
                'start_date',
                'end_date',
            ] as const)}
            endpoint="/api/stock-movements"
            queryKey={['stock-movements']}
            entityName="Stock Movement"
            exportEndpoint="/api/stock-movements/export"
        />
    );
}
