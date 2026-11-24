'use client';

import { StockAdjustmentRowActions } from '@/components/stock-adjustments/StockAdjustmentRowActions';
import { type StockAdjustment } from '@/types/stock-adjustment';
import {
    createAdjustmentTypeBadgeColumn,
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

export const stockAdjustmentColumns: ColumnDef<StockAdjustment>[] = [
    createSelectColumn<StockAdjustment>(),
    createTextColumn<StockAdjustment>({
        accessorKey: 'adjustment_number',
        label: 'Adjustment Number',
        primary: true,
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
    createAdjustmentTypeBadgeColumn<StockAdjustment>({
        accessorKey: 'adjustment_type',
        label: 'Adjustment Type',
    }),
    createStatusBadgeColumn<StockAdjustment>({
        accessorKey: 'status',
        label: 'Status',
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row, table }) => {
            const item = row.original;
            const meta = table.options.meta as CustomTableMeta<StockAdjustment>;
            return (
                <StockAdjustmentRowActions
                    item={item}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
