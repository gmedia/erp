'use client';

import { createNumberColumn, createSortingHeader } from '@/utils/columns';
import type { ColumnDef } from '@tanstack/react-table';

export type InventoryStocktakeVarianceReportItem = {
    id: number;
    stocktake: {
        id: number;
        stocktake_number: string;
        stocktake_date: string | null;
    };
    product: {
        id: number;
        code: string | null;
        name: string;
        category: { id: number | null; name: string | null };
    };
    warehouse: {
        id: number;
        code: string | null;
        name: string;
        branch: { id: number | null; name: string | null };
    };
    system_quantity: string;
    counted_quantity: string;
    variance: string;
    result: 'surplus' | 'deficit' | string;
    counted_at: string | null;
    counted_by: { id: number; name: string } | null;
};

function formatDate(value: string | null | undefined): string {
    if (!value) return '-';
    return new Date(value).toLocaleString();
}

export const inventoryStocktakeVarianceColumns: ColumnDef<InventoryStocktakeVarianceReportItem>[] = [
    {
        id: 'stocktake_number',
        accessorKey: 'stocktake.stocktake_number',
        ...createSortingHeader('Stocktake No.'),
        cell: ({ row }) => <div>{row.original.stocktake?.stocktake_number ?? '-'}</div>,
    },
    {
        id: 'stocktake_date',
        accessorKey: 'stocktake.stocktake_date',
        ...createSortingHeader('Stocktake Date'),
        cell: ({ row }) => <div>{row.original.stocktake?.stocktake_date ?? '-'}</div>,
    },
    {
        id: 'product_name',
        accessorKey: 'product.name',
        ...createSortingHeader('Product'),
        cell: ({ row }) => (
            <div className="space-y-0.5">
                <div className="font-medium">{row.original.product?.name ?? '-'}</div>
                <div className="text-xs text-muted-foreground">{row.original.product?.code ?? '-'}</div>
            </div>
        ),
    },
    {
        id: 'category_name',
        accessorKey: 'product.category.name',
        ...createSortingHeader('Category'),
        cell: ({ row }) => <div>{row.original.product?.category?.name ?? '-'}</div>,
    },
    {
        id: 'warehouse_name',
        accessorKey: 'warehouse.name',
        ...createSortingHeader('Warehouse'),
        cell: ({ row }) => (
            <div className="space-y-0.5">
                <div className="font-medium">{row.original.warehouse?.name ?? '-'}</div>
                <div className="text-xs text-muted-foreground">{row.original.warehouse?.branch?.name ?? '-'}</div>
            </div>
        ),
    },
    createNumberColumn<InventoryStocktakeVarianceReportItem>({
        accessorKey: 'system_quantity',
        label: 'System Qty',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<InventoryStocktakeVarianceReportItem>({
        accessorKey: 'counted_quantity',
        label: 'Counted Qty',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<InventoryStocktakeVarianceReportItem>({
        accessorKey: 'variance',
        label: 'Variance',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    {
        id: 'result',
        accessorKey: 'result',
        ...createSortingHeader('Result'),
        cell: ({ row }) => <div className="capitalize">{row.original.result ?? '-'}</div>,
    },
    {
        id: 'counted_at',
        accessorKey: 'counted_at',
        ...createSortingHeader('Counted At'),
        cell: ({ row }) => <div>{formatDate(row.original.counted_at)}</div>,
    },
];
