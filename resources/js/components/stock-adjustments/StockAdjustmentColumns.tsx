'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { type StockAdjustment } from '@/types/stock-adjustment';
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

export const stockAdjustmentColumns: ColumnDef<StockAdjustment>[] = [
    createSelectColumn<StockAdjustment>(),
    createTextColumn<StockAdjustment>({
        accessorKey: 'adjustment_number',
        label: 'Adjustment Number',
    }),
    {
        id: 'warehouse_id',
        accessorFn: (row) => row.warehouse?.name ?? '',
        ...createSortingHeader('Warehouse'),
        cell: ({ row }) => row.original.warehouse?.name || '-',
    },
    createDateColumn<StockAdjustment>({
        accessorKey: 'adjustment_date',
        label: 'Adjustment Date',
    }),
    createBadgeColumn<StockAdjustment>({
        accessorKey: 'adjustment_type',
        label: 'Adjustment Type',
        colorMap: {
            damage: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            expired:
                'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400',
            shrinkage:
                'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
            correction:
                'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
            stocktake_result:
                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400',
            initial_stock:
                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            other: 'bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300',
        },
    }),
    createBadgeColumn<StockAdjustment>({
        accessorKey: 'status',
        label: 'Status',
        colorMap: {
            draft: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
            pending_approval:
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            approved:
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
            const meta = table.options.meta as CustomTableMeta<StockAdjustment>;
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
