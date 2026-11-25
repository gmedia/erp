'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { type StockTransfer } from '@/types/stock-transfer';
import {
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
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
        label: { key: 'column.transfer_number', fallback: 'Transfer Number' },
        primary: true,
    }),
    {
        id: 'from_warehouse_id',
        accessorFn: (row) => row.from_warehouse?.name ?? '',
        ...createSortingHeader({
            key: 'column.from_warehouse',
            fallback: 'From Warehouse',
        }),
        cell: ({ row }) => row.original.from_warehouse?.name || '-',
    },
    {
        id: 'to_warehouse_id',
        accessorFn: (row) => row.to_warehouse?.name ?? '',
        ...createSortingHeader({
            key: 'column.to_warehouse',
            fallback: 'To Warehouse',
        }),
        cell: ({ row }) => row.original.to_warehouse?.name || '-',
    },
    createDateColumn<StockTransfer>({
        accessorKey: 'transfer_date',
        label: { key: 'column.transfer_date', fallback: 'Transfer Date' },
    }),
    createDateColumn<StockTransfer>({
        accessorKey: 'expected_arrival_date',
        label: { key: 'column.expected_arrival', fallback: 'Expected Arrival' },
    }),
    createStatusBadgeColumn<StockTransfer>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
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
