import {
    createReportStatusBadgeColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/utils';
import { createSortingHeader } from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { ColumnDef } from '@tanstack/react-table';

export interface MaintenanceCostReportItem {
    id: number;
    asset_code: string | null;
    asset_name: string | null;
    category_name: string | null;
    branch_name: string | null;
    maintenance_type: string;
    status: string;
    scheduled_at: string | null;
    performed_at: string | null;
    supplier_name: string | null;
    cost: number;
    notes: string | null;
}

export const maintenanceCostColumns: ColumnDef<MaintenanceCostReportItem>[] = [
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'asset_code',
        header: 'Asset Code',
        getValue: (item) => item.asset_code,
        className: 'font-medium',
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'asset_name',
        header: 'Asset Name',
        getValue: (item) => item.asset_name,
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'category_name',
        header: 'Category',
        getValue: (item) => item.category_name,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'branch_name',
        header: 'Branch',
        getValue: (item) => item.branch_name,
    }),
    createReportStatusBadgeColumn<MaintenanceCostReportItem>({
        accessorKey: 'maintenance_type',
        header: 'Type',
        getValue: (item) => item.maintenance_type,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'status',
        header: 'Status',
        getValue: (item) => item.status?.replace('_', ' '),
        className: 'capitalize',
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'scheduled_at',
        header: 'Scheduled At',
        getValue: (item) => formatDateByRegionalSettings(item.scheduled_at),
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'performed_at',
        header: 'Performed At',
        getValue: (item) => formatDateByRegionalSettings(item.performed_at),
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'supplier_name',
        header: 'Vendor',
        getValue: (item) => item.supplier_name,
        sortable: true,
    }),
    {
        accessorKey: 'cost',
        ...createSortingHeader('Cost'),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrency(row.getValue('cost'))}
            </div>
        ),
    },
];
