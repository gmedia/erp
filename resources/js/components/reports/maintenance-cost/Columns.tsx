import { ColumnDef } from '@tanstack/react-table';
import { formatCurrency } from '@/lib/utils';
import { createSortingHeader } from '@/utils/columns';
import { Badge } from '@/components/ui/badge';

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
    {
        accessorKey: 'asset_code',
        ...createSortingHeader('Asset Code'),
        cell: ({ row }) => <div className="font-medium">{row.getValue('asset_code') || '-'}</div>,
    },
    {
        accessorKey: 'asset_name',
        ...createSortingHeader('Asset Name'),
        cell: ({ row }) => <div>{row.getValue('asset_name') || '-'}</div>,
    },
    {
        accessorKey: 'category_name',
        header: 'Category',
        cell: ({ row }) => <div>{row.getValue('category_name') || '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'branch_name',
        header: 'Branch',
        cell: ({ row }) => <div>{row.getValue('branch_name') || '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'maintenance_type',
        ...createSortingHeader('Type'),
        cell: ({ row }) => <Badge variant="outline" className="capitalize">{(row.getValue('maintenance_type') as string).replace('_', ' ')}</Badge>,
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            return <div className="capitalize">{status.replace('_', ' ')}</div>;
        },
    },
    {
        accessorKey: 'scheduled_at',
        ...createSortingHeader('Scheduled At'),
        cell: ({ row }) => {
            const date = row.getValue('scheduled_at') as string;
            return <div>{date ? new Date(date).toLocaleDateString() : '-'}</div>;
        },
    },
    {
        accessorKey: 'performed_at',
        ...createSortingHeader('Performed At'),
        cell: ({ row }) => {
            const date = row.getValue('performed_at') as string;
            return <div>{date ? new Date(date).toLocaleDateString() : '-'}</div>;
        },
    },
    {
        accessorKey: 'supplier_name',
        ...createSortingHeader('Vendor'),
        cell: ({ row }) => <div>{row.getValue('supplier_name') || '-'}</div>,
    },
    {
        accessorKey: 'cost',
        ...createSortingHeader('Cost'),
        cell: ({ row }) => <div className="text-right">{formatCurrency(row.getValue('cost'))}</div>,
    },
];
