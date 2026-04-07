'use client';

import {
    createReportSummaryColumn,
    createReportTextColumn,
    createReportWarehouseColumn,
} from '@/components/common/ReportColumns';
import { createNumberColumn } from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type StockMonitorItem = {
    id: number;
    product: {
        id: number;
        code: string | null;
        name: string;
        category: { id: number; name: string } | null;
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

export function createStockMonitorColumns(): ColumnDef<StockMonitorItem>[] {
    return [
        createReportSummaryColumn<StockMonitorItem>({
            id: 'product_name',
            header: 'Product',
            getPrimary: (item) => item.product?.name,
            getSecondary: (item) => item.product?.code,
        }),
        createReportTextColumn<StockMonitorItem>({
            id: 'category_name',
            header: 'Category',
            getValue: (item) => item.product?.category?.name,
        }),
        createReportWarehouseColumn<StockMonitorItem>({
            id: 'warehouse_name',
            header: 'Warehouse',
            getWarehouse: (item) => item.warehouse,
        }),
        createNumberColumn<StockMonitorItem>({
            accessorKey: 'quantity_on_hand',
            label: 'Qty On Hand',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockMonitorItem>({
            accessorKey: 'average_cost',
            label: 'Avg Cost',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockMonitorItem>({
            accessorKey: 'stock_value',
            label: 'Stock Value',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createReportTextColumn<StockMonitorItem>({
            accessorKey: 'moved_at',
            header: 'Last Movement',
            getValue: (item) => formatDate(item.moved_at),
            sortable: true,
        }),
    ];
}
