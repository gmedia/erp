'use client';

import { createNumberColumn, createSortingHeader } from '@/utils/columns';
import type { ColumnDef } from '@tanstack/react-table';

export type StockAdjustmentReportItem = {
    adjustment_date: string | null;
    adjustment_type: string;
    status: string;
    warehouse: {
        id: number;
        code: string | null;
        name: string;
        branch: { id: number | null; name: string | null };
    };
    adjustment_count: number;
    total_quantity_adjusted: string;
    total_adjustment_value: string;
};

export const stockAdjustmentReportColumns: ColumnDef<StockAdjustmentReportItem>[] = [
    {
        accessorKey: 'adjustment_date',
        ...createSortingHeader('Adjustment Date'),
        cell: ({ row }) => <div>{row.original.adjustment_date ?? '-'}</div>,
    },
    {
        accessorKey: 'adjustment_type',
        ...createSortingHeader('Adjustment Type'),
        cell: ({ row }) => <div className="capitalize">{(row.original.adjustment_type ?? '-').replace(/_/g, ' ')}</div>,
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => <div className="capitalize">{(row.original.status ?? '-').replace(/_/g, ' ')}</div>,
    },
    {
        accessorKey: 'warehouse.name',
        ...createSortingHeader('Warehouse'),
        cell: ({ row }) => (
            <div className="space-y-0.5">
                <div className="font-medium">{row.original.warehouse?.name ?? '-'}</div>
                <div className="text-xs text-muted-foreground">
                    {(row.original.warehouse?.code ?? '-') +
                        (row.original.warehouse?.branch?.name ? ` • ${row.original.warehouse.branch.name}` : '')}
                </div>
            </div>
        ),
    },
    createNumberColumn<StockAdjustmentReportItem>({
        accessorKey: 'adjustment_count',
        label: 'Adjustment Count',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }),
    createNumberColumn<StockAdjustmentReportItem>({
        accessorKey: 'total_quantity_adjusted',
        label: 'Total Qty Adjusted',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<StockAdjustmentReportItem>({
        accessorKey: 'total_adjustment_value',
        label: 'Total Adjustment Value',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
];
