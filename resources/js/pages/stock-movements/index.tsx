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
            titleKey="page.stock_movements.title"
            description="Digital stock card — product movements by warehouse, type, and period."
            descriptionKey="page.stock_movements.description"
            breadcrumbs={[
                { title: 'Inventory', href: '#' },
                {
                    title: 'Stock Movements',
                    titleKey: 'menu.stock_movement',
                    href: '/stock-movements',
                },
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
            defaultColumnVisibility={{
                created_by: false,
                warehouse_name: false,
                average_cost_after: false,
            }}
        />
    );
}
