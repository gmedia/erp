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
            damage: 'bg-destructive-soft text-destructive-foreground',
            expired: 'bg-warning-soft text-warning',
            shrinkage: 'bg-warning-soft text-warning',
            correction: 'bg-muted text-foreground',
            stocktake_result: 'bg-chart-3/10 text-chart-3',
            initial_stock: 'bg-success-soft text-success',
            other: 'bg-muted text-foreground',
        },
    }),
    createBadgeColumn<StockAdjustment>({
        accessorKey: 'status',
        label: 'Status',
        colorMap: {
            draft: 'bg-muted text-foreground',
            pending_approval: 'bg-warning-soft text-warning',
            approved: 'bg-success-soft text-success',
            cancelled: 'bg-destructive-soft text-destructive-foreground',
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
