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
            draft: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
            pending_approval:
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            approved:
                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            in_transit:
                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
            received:
                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            cancelled:
                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
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
