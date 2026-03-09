'use client';

import { createNumberColumn, createSortingHeader } from '@/utils/columns';
import type { ColumnDef } from '@tanstack/react-table';

export type StockMovementReportItem = {
    product: {
        id: number;
        code: string | null;
        name: string;
        category: { id: number; name: string | null };
    };
    warehouse: {
        id: number;
        code: string | null;
        name: string;
        branch: { id: number | null; name: string | null };
    };
    total_in: string;
    total_out: string;
    ending_balance: string;
    last_moved_at: string | null;
};

function formatDate(value: string | null | undefined): string {
    if (!value) return '-';
    return new Date(value).toLocaleString();
}

export const stockMovementReportColumns: ColumnDef<StockMovementReportItem>[] =
    [
        {
            accessorKey: 'product.name',
            ...createSortingHeader('Product'),
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
            accessorKey: 'product.category.name',
            ...createSortingHeader('Category'),
            cell: ({ row }) => (
                <div>{row.original.product?.category?.name ?? '-'}</div>
            ),
        },
        {
            accessorKey: 'warehouse.name',
            ...createSortingHeader('Warehouse'),
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
        createNumberColumn<StockMovementReportItem>({
            accessorKey: 'total_in',
            label: 'Total In',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockMovementReportItem>({
            accessorKey: 'total_out',
            label: 'Total Out',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockMovementReportItem>({
            accessorKey: 'ending_balance',
            label: 'Ending Balance',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        {
            accessorKey: 'last_moved_at',
            ...createSortingHeader('Last Movement'),
            cell: ({ row }) => (
                <div>{formatDate(row.original.last_moved_at)}</div>
            ),
        },
    ];
