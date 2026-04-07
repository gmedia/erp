'use client';

import {
    createReportTextColumn,
    createReportWarehouseColumn,
} from '@/components/common/ReportColumns';
import { createNumberColumn } from '@/utils/columns';
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

export const stockAdjustmentReportColumns: ColumnDef<StockAdjustmentReportItem>[] =
    [
        createReportTextColumn<StockAdjustmentReportItem>({
            accessorKey: 'adjustment_date',
            header: 'Adjustment Date',
            getValue: (item) => item.adjustment_date,
            sortable: true,
        }),
        createReportTextColumn<StockAdjustmentReportItem>({
            accessorKey: 'adjustment_type',
            header: 'Adjustment Type',
            getValue: (item) =>
                (item.adjustment_type ?? '-').replaceAll('_', ' '),
            className: 'capitalize',
            sortable: true,
        }),
        createReportTextColumn<StockAdjustmentReportItem>({
            accessorKey: 'status',
            header: 'Status',
            getValue: (item) => (item.status ?? '-').replaceAll('_', ' '),
            className: 'capitalize',
            sortable: true,
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
