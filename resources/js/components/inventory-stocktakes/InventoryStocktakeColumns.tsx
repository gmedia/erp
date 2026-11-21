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
    createStatusBadgeColumn<InventoryStocktake>({
        accessorKey: 'status',
        label: 'Status',
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
