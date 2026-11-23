'use client';

import {
    createReportBreadcrumbs,
    ReportDataTablePage,
} from '@/components/common/ReportDataTablePage';
import {
    maintenanceCostColumns,
    MaintenanceCostReportItem,
} from '@/components/reports/maintenance-cost/Columns';
import { createMaintenanceCostReportFilterFields } from '@/components/reports/maintenance-cost/Filters';

export default function MaintenanceCostReport() {
    return (
        <ReportDataTablePage<MaintenanceCostReportItem>
            title="Maintenance Cost Report"
            descriptionKey="reports.pageDescriptions.maintenance-cost"
            breadcrumbs={createReportBreadcrumbs(
                'Maintenance Cost',
                '/reports/maintenance-cost',
            )}
            columns={maintenanceCostColumns}
            filterFields={createMaintenanceCostReportFilterFields()}
            initialFilters={{
                search: '',
                asset_category_id: '',
                branch_id: '',
                supplier_id: '',
                maintenance_type: '',
                status: '',
                start_date: '',
                end_date: '',
            }}
            endpoint="/api/reports/maintenance-cost"
            queryKey={['maintenance-cost-report']}
            entityName="Maintenance Cost"
            exportEndpoint="/api/reports/maintenance-cost/export"
            defaultColumnVisibility={{
                branch_name: false,
                scheduled_at: false,
                performed_at: false,
            }}
        />
    );
}
