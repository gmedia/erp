'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { type InventoryStocktake } from '@/types/inventory-stocktake';
import {
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
    type CustomTableMeta,
} from '@/utils/columns';
import { type ColumnDef } from '@tanstack/react-table';

export const inventoryStocktakeColumns: ColumnDef<InventoryStocktake>[] = [
    createSelectColumn<InventoryStocktake>(),
    createTextColumn<InventoryStocktake>({
        accessorKey: 'stocktake_number',
        label: {
            key: 'column.stocktake_number',
            fallback: 'Stocktake Number',
        },
    }),
    {
        id: 'warehouse_id',
        accessorFn: (row) => row.warehouse?.name ?? '',
        ...createSortingHeader({
            key: 'column.warehouse',
            fallback: 'Warehouse',
        }),
        cell: ({ row }) => row.original.warehouse?.name || '-',
    },
    {
        id: 'product_category_id',
        accessorFn: (row) => row.product_category?.name ?? '',
        ...createSortingHeader({
            key: 'column.product_category',
            fallback: 'Product Category',
        }),
        cell: ({ row }) => row.original.product_category?.name || '-',
    },
    createDateColumn<InventoryStocktake>({
        accessorKey: 'stocktake_date',
        label: { key: 'column.stocktake_date', fallback: 'Stocktake Date' },
    }),
    createStatusBadgeColumn<InventoryStocktake>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    {
        id: 'actions',
        enableHiding: false,
        cell: ({ row, table }) => {
            const item = row.original;
            const meta = table.options
                .meta as CustomTableMeta<InventoryStocktake>;
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
