'use client';

import {
    SummaryCell,
    TextCell,
    WarehouseSummaryCell,
} from '@/components/common/ReportColumns';
import { createNumberColumn, createSortingHeader } from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
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
    return formatDateTimeByRegionalSettings(value);
}

export const stockMovementReportColumns: ColumnDef<StockMovementReportItem>[] =
    [
        {
            accessorKey: 'product.name',
            ...createSortingHeader('Product'),
            cell: ({ row }) => (
                <SummaryCell
                    primary={row.original.product?.name}
                    secondary={row.original.product?.code}
                />
            ),
        },
        {
            accessorKey: 'product.category.name',
            ...createSortingHeader('Category'),
            cell: ({ row }) => (
                <TextCell value={row.original.product?.category?.name} />
            ),
        },
        {
            accessorKey: 'warehouse.name',
            ...createSortingHeader('Warehouse'),
            cell: ({ row }) => (
                <WarehouseSummaryCell warehouse={row.original.warehouse} />
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
                <TextCell value={formatDate(row.original.last_moved_at)} />
            ),
        },
    ];
