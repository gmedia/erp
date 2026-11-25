'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import { SupplierReturn } from '@/types/supplier-return';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const renderPoNumberCell = ({ row }: { row: { original: SupplierReturn } }) => (
    <div>{row.original.purchase_order?.po_number ?? '-'}</div>
);

const renderGrNumberCell = ({ row }: { row: { original: SupplierReturn } }) => (
    <div>{row.original.goods_receipt?.gr_number ?? '-'}</div>
);

const renderSupplierCell = ({ row }: { row: { original: SupplierReturn } }) => (
    <div>{row.original.supplier?.name ?? '-'}</div>
);

const renderWarehouseCell = ({
    row,
}: {
    row: { original: SupplierReturn };
}) => <div>{row.original.warehouse?.name ?? '-'}</div>;

const renderStatusCell = ({ row }: { row: { original: SupplierReturn } }) => (
    <Badge variant={statusBadgeVariant(row.original.status)}>
        {formatStatusLabel(row.original.status)}
    </Badge>
);

export const supplierReturnColumns: ColumnDef<SupplierReturn>[] = [
    createSelectColumn<SupplierReturn>(),
    createTextColumn<SupplierReturn>({
        accessorKey: 'return_number',
        label: { key: 'column.return_number', fallback: 'Return Number' },
    }),
    {
        accessorKey: 'purchase_order',
        ...createSortingHeader({
            key: 'column.po_number',
            fallback: 'PO Number',
        }),
        cell: renderPoNumberCell,
    },
    {
        accessorKey: 'goods_receipt',
        ...createSortingHeader({
            key: 'column.gr_number',
            fallback: 'GR Number',
        }),
        cell: renderGrNumberCell,
    },
    {
        accessorKey: 'supplier',
        ...createSortingHeader({
            key: 'column.supplier',
            fallback: 'Supplier',
        }),
        cell: renderSupplierCell,
    },
    {
        accessorKey: 'warehouse',
        ...createSortingHeader({
            key: 'column.warehouse',
            fallback: 'Warehouse',
        }),
        cell: renderWarehouseCell,
    },
    createDateColumn<SupplierReturn>({
        accessorKey: 'return_date',
        label: { key: 'column.return_date', fallback: 'Return Date' },
    }),
    {
        accessorKey: 'reason',
        ...createSortingHeader({ key: 'column.reason', fallback: 'Reason' }),
        cell: ({ row }) => (
            <span>{formatStatusLabel(row.original.reason)}</span>
        ),
    },
    {
        accessorKey: 'status',
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: renderStatusCell,
    },
    createActionsColumn<SupplierReturn>(),
];
