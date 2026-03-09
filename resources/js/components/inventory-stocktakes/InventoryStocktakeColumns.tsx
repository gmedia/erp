'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { type InventoryStocktake } from '@/types/inventory-stocktake';
import {
    createBadgeColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { type ColumnDef } from '@tanstack/react-table';
import { type CustomTableMeta } from '@/utils/columns';

export const inventoryStocktakeColumns: ColumnDef<InventoryStocktake>[] = [
    createSelectColumn<InventoryStocktake>(),
    createTextColumn<InventoryStocktake>({
        accessorKey: 'stocktake_number',
        label: 'Stocktake Number',
    }),
    {
        id: 'warehouse_id',
        accessorFn: (row) => row.warehouse?.name ?? '',
        ...createSortingHeader('Warehouse'),
        cell: ({ row }) => row.original.warehouse?.name || '-',
    },
    {
        id: 'product_category_id',
        accessorFn: (row) => row.product_category?.name ?? '',
        ...createSortingHeader('Product Category'),
        cell: ({ row }) => row.original.product_category?.name || '-',
    },
    createDateColumn<InventoryStocktake>({
        accessorKey: 'stocktake_date',
        label: 'Stocktake Date',
    }),
    createBadgeColumn<InventoryStocktake>({
        accessorKey: 'status',
        label: 'Status',
        colorMap: {
            draft: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-100',
            in_progress:
                'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            completed:
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
            const meta = table.options.meta as CustomTableMeta<InventoryStocktake>;
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
