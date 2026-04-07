'use client';

import {
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
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
            header: 'Stocktake No.',
            getValue: (item) => item.stocktake?.stocktake_number,
            sortable: true,
        }),
        createReportTextColumn<InventoryStocktakeVarianceReportItem>({
            id: 'stocktake_date',
            accessorKey: 'stocktake.stocktake_date',
            header: 'Stocktake Date',
            getValue: (item) =>
                formatDateByRegionalSettings(item.stocktake?.stocktake_date),
            sortable: true,
        }),
        createReportSummaryColumn<InventoryStocktakeVarianceReportItem>({
            id: 'product_name',
            accessorKey: 'product.name',
            header: 'Product',
            getPrimary: (item) => item.product?.name,
            getSecondary: (item) => item.product?.code,
            sortable: true,
        }),
        createReportTextColumn<InventoryStocktakeVarianceReportItem>({
            id: 'category_name',
            accessorKey: 'product.category.name',
            header: 'Category',
            getValue: (item) => item.product?.category?.name,
            sortable: true,
        }),
        createReportSummaryColumn<InventoryStocktakeVarianceReportItem>({
            id: 'warehouse_name',
            accessorKey: 'warehouse.name',
            header: 'Warehouse',
            getPrimary: (item) => item.warehouse?.name,
            getSecondary: (item) => item.warehouse?.branch?.name,
            sortable: true,
        }),
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
        createReportTextColumn<InventoryStocktakeVarianceReportItem>({
            id: 'result',
            accessorKey: 'result',
            header: 'Result',
            getValue: (item) => item.result,
            className: 'capitalize',
            sortable: true,
        }),
        createReportTextColumn<InventoryStocktakeVarianceReportItem>({
            id: 'counted_at',
            accessorKey: 'counted_at',
            header: 'Counted At',
            getValue: (item) => formatDate(item.counted_at),
            sortable: true,
        }),
    ];
