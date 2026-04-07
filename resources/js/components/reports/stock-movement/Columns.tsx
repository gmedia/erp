'use client';

import {
    createReportSummaryColumn,
    createReportTextColumn,
    createReportWarehouseColumn,
} from '@/components/common/ReportColumns';
import { createNumberColumn } from '@/utils/columns';
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
        createReportSummaryColumn<StockMovementReportItem>({
            accessorKey: 'product.name',
            header: 'Product',
            getPrimary: (item) => item.product?.name,
            getSecondary: (item) => item.product?.code,
            sortable: true,
        }),
        createReportTextColumn<StockMovementReportItem>({
            accessorKey: 'product.category.name',
            header: 'Category',
            getValue: (item) => item.product?.category?.name,
            sortable: true,
        }),
        createReportWarehouseColumn<StockMovementReportItem>({
            accessorKey: 'warehouse.name',
            header: 'Warehouse',
            getWarehouse: (item) => item.warehouse,
            sortable: true,
        }),
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
        createReportTextColumn<StockMovementReportItem>({
            accessorKey: 'last_moved_at',
            header: 'Last Movement',
            getValue: (item) => formatDate(item.last_moved_at),
            sortable: true,
        }),
    ];
