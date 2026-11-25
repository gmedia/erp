import {
    createReportStatusBadgeColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
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
        header: { key: 'column.asset_code', fallback: 'Asset Code' },
        getValue: (item) => item.asset_code,
        className: 'font-medium',
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'asset_name',
        header: { key: 'column.asset_name', fallback: 'Asset Name' },
        getValue: (item) => item.asset_name,
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'category_name',
        header: { key: 'column.category', fallback: 'Category' },
        getValue: (item) => item.category_name,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'branch_name',
        header: { key: 'column.branch', fallback: 'Branch' },
        getValue: (item) => item.branch_name,
    }),
    createReportStatusBadgeColumn<MaintenanceCostReportItem>({
        accessorKey: 'maintenance_type',
        header: { key: 'column.maintenance_type', fallback: 'Type' },
        getValue: (item) => item.maintenance_type,
    }),
    createReportStatusBadgeColumn<MaintenanceCostReportItem>({
        accessorKey: 'status',
        header: { key: 'column.status', fallback: 'Status' },
        getValue: (item) => item.status,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'scheduled_at',
        header: { key: 'column.scheduled_at', fallback: 'Scheduled At' },
        getValue: (item) => formatDateByRegionalSettings(item.scheduled_at),
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'performed_at',
        header: { key: 'column.performed_at', fallback: 'Performed At' },
        getValue: (item) => formatDateByRegionalSettings(item.performed_at),
        sortable: true,
    }),
    createReportTextColumn<MaintenanceCostReportItem>({
        accessorKey: 'supplier_name',
        header: { key: 'column.vendor', fallback: 'Vendor' },
        getValue: (item) => item.supplier_name,
        sortable: true,
    }),
    {
        accessorKey: 'cost',
        meta: { numeric: true },
        ...createSortingHeader(
            { key: 'column.cost', fallback: 'Cost' },
            { align: 'right' },
        ),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrency(row.getValue('cost'))}
            </div>
        ),
    },
];
