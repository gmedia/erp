'use client';

import { createNumberColumn, createSortingHeader } from '@/utils/columns';
import type { ColumnDef } from '@tanstack/react-table';

export type InventoryValuationItem = {
    id: number;
    product: {
        id: number;
        code: string | null;
        name: string;
        category: { id: number; name: string } | null;
        unit: { id: number; name: string } | null;
    } | null;
    warehouse: {
        id: number;
        code: string | null;
        name: string;
        branch: { id: number; name: string } | null;
    } | null;
    quantity_on_hand: string;
    average_cost: string;
    stock_value: string;
    moved_at: string | null;
};

function formatDate(value: string | null | undefined): string {
    if (!value) return '-';
    return new Date(value).toLocaleString();
}

export const inventoryValuationColumns: ColumnDef<InventoryValuationItem>[] = [
    {
        id: 'product_name',
        header: 'Product',
        cell: ({ row }) => (
            <div className="space-y-0.5">
                <div className="font-medium">
                    {row.original.product?.name ?? '-'}
                </div>
                <div className="text-xs text-muted-foreground">
                    {row.original.product?.code ?? '-'}
                </div>
            </div>
        ),
    },
    {
        id: 'category_name',
        header: 'Category',
        cell: ({ row }) => (
            <div>{row.original.product?.category?.name ?? '-'}</div>
        ),
    },
    {
        id: 'unit_name',
        header: 'Unit',
        cell: ({ row }) => <div>{row.original.product?.unit?.name ?? '-'}</div>,
    },
    {
        id: 'warehouse_name',
        header: 'Warehouse',
        cell: ({ row }) => (
            <div className="space-y-0.5">
                <div className="font-medium">
                    {row.original.warehouse?.name ?? '-'}
                </div>
                <div className="text-xs text-muted-foreground">
                    {(row.original.warehouse?.code ?? '-') +
                        (row.original.warehouse?.branch?.name
                            ? ` • ${row.original.warehouse.branch.name}`
                            : '')}
                </div>
            </div>
        ),
    },
    createNumberColumn<InventoryValuationItem>({
        accessorKey: 'quantity_on_hand',
        label: 'Qty On Hand',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<InventoryValuationItem>({
        accessorKey: 'average_cost',
        label: 'Avg Cost',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<InventoryValuationItem>({
        accessorKey: 'stock_value',
        label: 'Stock Value',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    {
        accessorKey: 'moved_at',
        ...createSortingHeader('Last Movement'),
        cell: ({ row }) => <div>{formatDate(row.original.moved_at)}</div>,
    },
];
