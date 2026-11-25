'use client';

import {
    createReportSummaryColumn,
    createReportTextColumn,
    createReportWarehouseColumn,
} from '@/components/common/ReportColumns';
import { createCurrencyColumn, createNumberColumn } from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
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
    return formatDateTimeByRegionalSettings(value);
}

export const inventoryValuationColumns: ColumnDef<InventoryValuationItem>[] = [
    createReportSummaryColumn<InventoryValuationItem>({
        id: 'product_name',
        header: { key: 'column.product', fallback: 'Product' },
        getPrimary: (item) => item.product?.name,
        getSecondary: (item) => item.product?.code,
    }),
    createReportTextColumn<InventoryValuationItem>({
        id: 'category_name',
        header: { key: 'column.category', fallback: 'Category' },
        getValue: (item) => item.product?.category?.name,
    }),
    createReportTextColumn<InventoryValuationItem>({
        id: 'unit_name',
        header: { key: 'column.unit', fallback: 'Unit' },
        getValue: (item) => item.product?.unit?.name,
    }),
    createReportWarehouseColumn<InventoryValuationItem>({
        id: 'warehouse_name',
        header: { key: 'column.warehouse', fallback: 'Warehouse' },
        getWarehouse: (item) => item.warehouse,
    }),
    createNumberColumn<InventoryValuationItem>({
        accessorKey: 'quantity_on_hand',
        label: { key: 'column.qty_on_hand', fallback: 'Qty On Hand' },
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<InventoryValuationItem>({
        accessorKey: 'average_cost',
        label: { key: 'column.avg_cost', fallback: 'Avg Cost' },
        currency: 'IDR',
        locale: 'id-ID',
    }),
    createCurrencyColumn<InventoryValuationItem>({
        accessorKey: 'stock_value',
        label: { key: 'column.stock_value', fallback: 'Stock Value' },
        currency: 'IDR',
        locale: 'id-ID',
    }),
    createReportTextColumn<InventoryValuationItem>({
        accessorKey: 'moved_at',
        header: { key: 'column.last_movement', fallback: 'Last Movement' },
        getValue: (item) => formatDate(item.moved_at),
        sortable: true,
    }),
];
