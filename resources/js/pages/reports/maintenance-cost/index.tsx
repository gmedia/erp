'use client';

import { ReportDataTablePage } from '@/components/common/ReportDataTablePage';
import {
    maintenanceCostColumns,
    MaintenanceCostReportItem,
} from '@/components/reports/maintenance-cost/Columns';
import { createMaintenanceCostReportFilterFields } from '@/components/reports/maintenance-cost/Filters';

export default function MaintenanceCostReport() {
    return (
        <ReportDataTablePage<MaintenanceCostReportItem>
            title="Maintenance Cost Report"
            breadcrumbs={[
                { title: 'Reports', href: '#' },
                {
                    title: 'Maintenance Cost',
                    href: '/reports/maintenance-cost',
                },
            ]}
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
            endpoint="/reports/maintenance-cost"
            queryKey={['maintenance-cost-report']}
            entityName="Maintenance Cost"
            exportEndpoint="/reports/maintenance-cost/export"
        />
    );
}
