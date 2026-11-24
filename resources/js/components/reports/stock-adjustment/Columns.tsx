'use client';

import {
    createReportStatusBadgeColumn,
    createReportTextColumn,
    createReportWarehouseColumn,
} from '@/components/common/ReportColumns';
import { createCurrencyColumn, createNumberColumn } from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type StockAdjustmentReportItem = {
    stock_adjustment_id: number;
    adjustment_number: string;
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
    total_increase_quantity: string;
    total_decrease_quantity: string;
    total_adjustment_value: string;
};

export const stockAdjustmentReportColumns: ColumnDef<StockAdjustmentReportItem>[] =
    [
        createReportTextColumn<StockAdjustmentReportItem>({
            accessorKey: 'adjustment_number',
            header: 'Adjustment No.',
            getValue: (item) => item.adjustment_number,
            sortable: true,
        }),
        createReportTextColumn<StockAdjustmentReportItem>({
            accessorKey: 'adjustment_date',
            header: 'Adjustment Date',
            getValue: (item) =>
                item.adjustment_date
                    ? formatDateByRegionalSettings(item.adjustment_date)
                    : '-',
            sortable: true,
        }),
        createReportStatusBadgeColumn<StockAdjustmentReportItem>({
            accessorKey: 'adjustment_type',
            header: 'Adjustment Type',
            getValue: (item) => item.adjustment_type,
            getVariant: () => 'outline',
        }),
        createReportStatusBadgeColumn<StockAdjustmentReportItem>({
            accessorKey: 'status',
            header: 'Status',
            getValue: (item) => item.status,
        }),
        createReportWarehouseColumn<StockAdjustmentReportItem>({
            accessorKey: 'warehouse.name',
            header: 'Warehouse',
            getWarehouse: (item) => item.warehouse,
            sortable: true,
        }),
        createNumberColumn<StockAdjustmentReportItem>({
            accessorKey: 'adjustment_count',
            label: 'Adjustment Count',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }),
        createNumberColumn<StockAdjustmentReportItem>({
            accessorKey: 'total_quantity_adjusted',
            label: 'Total Qty Adjusted',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockAdjustmentReportItem>({
            accessorKey: 'total_increase_quantity',
            label: 'Increase Qty',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<StockAdjustmentReportItem>({
            accessorKey: 'total_decrease_quantity',
            label: 'Decrease Qty',
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<StockAdjustmentReportItem>({
            accessorKey: 'total_adjustment_value',
            label: 'Total Adjustment Value',
        }),
    ];
