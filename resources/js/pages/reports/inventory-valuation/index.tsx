'use client';

import {
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    inventoryValuationColumns,
    type InventoryValuationItem,
} from '@/components/reports/inventory-valuation/Columns';
import { createInventoryValuationFilterFields } from '@/components/reports/inventory-valuation/Filters';

export default function InventoryValuationReportPage() {
    return (
        <ReportDataTablePage<InventoryValuationItem>
            title="Inventory Valuation Report"
            breadcrumbs={createReportBreadcrumbs(
                'Inventory Valuation',
                '/reports/inventory-valuation',
            )}
            columns={inventoryValuationColumns}
            filterFields={createInventoryValuationFilterFields()}
            initialFilters={{
                search: '',
                product_id: '',
                warehouse_id: '',
                branch_id: '',
                category_id: '',
            }}
            endpoint="/api/reports/inventory-valuation"
            queryKey={['inventory-valuation-report']}
            entityName="Inventory Valuation"
            exportEndpoint="/api/reports/inventory-valuation/export"
        />
    );
}
