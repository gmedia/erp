'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { type StockTransfer } from '@/types/stock-transfer';
import {
    createBadgeColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { type ColumnDef, type TableMeta } from '@tanstack/react-table';

interface CustomTableMeta<T> extends TableMeta<T> {
    onView?: (item: T) => void;
    onEdit?: (item: T) => void;
    onDelete?: (item: T) => void;
}

export const stockTransferColumns: ColumnDef<StockTransfer>[] = [
    createSelectColumn<StockTransfer>(),
    createTextColumn<StockTransfer>({
        accessorKey: 'transfer_number',
        label: 'Transfer Number',
    }),
    {
        id: 'from_warehouse_id',
        accessorFn: (row) => row.from_warehouse?.name ?? '',
        ...createSortingHeader('From Warehouse'),
        cell: ({ row }) => row.original.from_warehouse?.name || '-',
    },
    {
        id: 'to_warehouse_id',
        accessorFn: (row) => row.to_warehouse?.name ?? '',
        ...createSortingHeader('To Warehouse'),
        cell: ({ row }) => row.original.to_warehouse?.name || '-',
    },
    createDateColumn<StockTransfer>({
        accessorKey: 'transfer_date',
        label: 'Transfer Date',
    }),
    createDateColumn<StockTransfer>({
        accessorKey: 'expected_arrival_date',
        label: 'Expected Arrival',
    }),
    createBadgeColumn<StockTransfer>({
        accessorKey: 'status',
        label: 'Status',
        colorMap: {
            draft: 'bg-muted text-foreground',
            pending_approval: 'bg-warning-soft text-warning',
            approved: 'bg-info/10 text-info',
            in_transit: 'bg-chart-3/10 text-chart-3',
            received: 'bg-success-soft text-success',
            cancelled: 'bg-destructive-soft text-destructive-foreground',
        },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row, table }) => {
            const item = row.original;
            const meta = table.options.meta as CustomTableMeta<StockTransfer>;
            return (
                <GenericActions
                    item={item}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
