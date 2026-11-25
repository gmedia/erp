'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import {
    formatStatusLabel,
    stocktakeVarianceBadgeVariant,
} from '@/lib/status-badge';
import { createNumberColumn } from '@/utils/columns';
import {
    formatDateByRegionalSettings,
    formatDateTimeByRegionalSettings,
} from '@/utils/date-format';
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
    result: string;
    counted_at: string | null;
    counted_by: { id: number; name: string } | null;
};

function formatDate(value: string | null | undefined): string {
    return formatDateTimeByRegionalSettings(value);
}

export const inventoryStocktakeVarianceColumns: ColumnDef<InventoryStocktakeVarianceReportItem>[] =
    [
        createReportTextColumn<InventoryStocktakeVarianceReportItem>({
            id: 'stocktake_number',
            accessorKey: 'stocktake.stocktake_number',
            header: {
                key: 'column.stocktake_number',
                fallback: 'Stocktake No.',
            },
            getValue: (item) => item.stocktake?.stocktake_number,
            sortable: true,
        }),
        createReportTextColumn<InventoryStocktakeVarianceReportItem>({
            id: 'stocktake_date',
            accessorKey: 'stocktake.stocktake_date',
            header: {
                key: 'column.stocktake_date',
                fallback: 'Stocktake Date',
            },
            getValue: (item) =>
                formatDateByRegionalSettings(item.stocktake?.stocktake_date),
            sortable: true,
        }),
        createReportSummaryColumn<InventoryStocktakeVarianceReportItem>({
            id: 'product_name',
            accessorKey: 'product.name',
            header: { key: 'column.product', fallback: 'Product' },
            getPrimary: (item) => item.product?.name,
            getSecondary: (item) => item.product?.code,
            sortable: true,
        }),
        createReportTextColumn<InventoryStocktakeVarianceReportItem>({
            id: 'category_name',
            accessorKey: 'product.category.name',
            header: { key: 'column.category', fallback: 'Category' },
            getValue: (item) => item.product?.category?.name,
            sortable: true,
        }),
        createReportSummaryColumn<InventoryStocktakeVarianceReportItem>({
            id: 'warehouse_name',
            accessorKey: 'warehouse.name',
            header: { key: 'column.warehouse', fallback: 'Warehouse' },
            getPrimary: (item) => item.warehouse?.name,
            getSecondary: (item) => item.warehouse?.branch?.name,
            sortable: true,
        }),
        createNumberColumn<InventoryStocktakeVarianceReportItem>({
            accessorKey: 'system_quantity',
            label: { key: 'column.system_qty', fallback: 'System Qty' },
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<InventoryStocktakeVarianceReportItem>({
            accessorKey: 'counted_quantity',
            label: { key: 'column.counted_qty', fallback: 'Counted Qty' },
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<InventoryStocktakeVarianceReportItem>({
            accessorKey: 'variance',
            label: { key: 'column.variance', fallback: 'Variance' },
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createReportStatusBadgeColumn<InventoryStocktakeVarianceReportItem>({
            id: 'result',
            accessorKey: 'result',
            header: { key: 'column.result', fallback: 'Result' },
            getValue: (item) => formatStatusLabel(item.result),
            getVariant: (item) => stocktakeVarianceBadgeVariant(item.result),
        }),
        createReportTextColumn<InventoryStocktakeVarianceReportItem>({
            id: 'counted_at',
            accessorKey: 'counted_at',
            header: { key: 'column.counted_at', fallback: 'Counted At' },
            getValue: (item) => formatDate(item.counted_at),
            sortable: true,
        }),
    ];
