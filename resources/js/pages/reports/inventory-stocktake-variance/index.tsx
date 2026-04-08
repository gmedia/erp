'use client';

import {
    createEmptyReportFilters,
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    inventoryStocktakeVarianceColumns,
    type InventoryStocktakeVarianceReportItem,
} from '@/components/reports/inventory-stocktake-variance/Columns';
import { createInventoryStocktakeVarianceFilterFields } from '@/components/reports/inventory-stocktake-variance/Filters';

export default function InventoryStocktakeVarianceReportPage() {
    return (
        <ReportDataTablePage<InventoryStocktakeVarianceReportItem>
            title="Inventory Stocktake Variance Report"
            breadcrumbs={createReportBreadcrumbs(
                'Inventory Stocktake Variance',
                '/reports/inventory-stocktake-variance',
            )}
            columns={inventoryStocktakeVarianceColumns}
            filterFields={createInventoryStocktakeVarianceFilterFields()}
            initialFilters={createEmptyReportFilters([
                'inventory_stocktake_id',
                'product_id',
                'warehouse_id',
                'branch_id',
                'category_id',
                'result',
                'start_date',
                'end_date',
            ])}
            endpoint="/api/reports/inventory-stocktake-variance"
            queryKey={['inventory-stocktake-variance-report']}
            entityName="Inventory Stocktake Variance"
            exportEndpoint="/api/reports/inventory-stocktake-variance/export"
        />
    );
}
