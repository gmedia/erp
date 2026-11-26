'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
    createReportWarehouseColumn,
} from '@/components/common/ReportColumns';
import { createCurrencyColumn, createNumberColumn } from '@/utils/columns';
import {
    FORMAT_LOCALE,
    formatDate as formatDateValue,
} from '@/utils/formatters';
import type { ColumnDef } from '@tanstack/react-table';

export type GoodsReceiptReportItem = {
    goods_receipt: {
        id: number;
        gr_number: string | null;
        receipt_date: string | null;
        status: string;
    };
    purchase_order: {
        id: number;
        po_number: string | null;
    };
    supplier: {
        id: number;
        name: string;
    };
    warehouse: {
        id: number;
        code: string | null;
        name: string;
    };
    item_count: number;
    total_received_quantity: string;
    total_accepted_quantity: string;
    total_rejected_quantity: string;
    total_receipt_value: string;
};

function formatDate(value: string | null | undefined): string {
    return formatDateValue(value);
}

export const goodsReceiptReportColumns: ColumnDef<GoodsReceiptReportItem>[] = [
    createReportSummaryColumn<GoodsReceiptReportItem>({
        accessorKey: 'goods_receipt.gr_number',
        header: { key: 'column.gr_number', fallback: 'GR Number' },
        getPrimary: (item) => item.goods_receipt?.gr_number,
        getSecondary: (item) => formatDate(item.goods_receipt?.receipt_date),
        sortable: true,
    }),
    createReportTextColumn<GoodsReceiptReportItem>({
        accessorKey: 'purchase_order.po_number',
        header: { key: 'column.po_number', fallback: 'PO Number' },
        getValue: (item) => item.purchase_order?.po_number,
        sortable: true,
    }),
    createReportTextColumn<GoodsReceiptReportItem>({
        accessorKey: 'supplier.name',
        header: { key: 'column.supplier', fallback: 'Supplier' },
        getValue: (item) => item.supplier?.name,
        sortable: true,
    }),
    createReportWarehouseColumn<GoodsReceiptReportItem>({
        accessorKey: 'warehouse.name',
        header: { key: 'column.warehouse', fallback: 'Warehouse' },
        getWarehouse: (item) => item.warehouse,
        sortable: true,
    }),
    createReportStatusBadgeColumn<GoodsReceiptReportItem>({
        accessorKey: 'goods_receipt.status',
        header: { key: 'column.status', fallback: 'Status' },
        getValue: (item) => item.goods_receipt?.status,
    }),
    createNumberColumn<GoodsReceiptReportItem>({
        accessorKey: 'item_count',
        label: { key: 'column.item_count', fallback: 'Item Count' },
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }),
    createNumberColumn<GoodsReceiptReportItem>({
        accessorKey: 'total_received_quantity',
        label: { key: 'column.received_qty', fallback: 'Received Qty' },
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<GoodsReceiptReportItem>({
        accessorKey: 'total_accepted_quantity',
        label: { key: 'column.accepted_qty', fallback: 'Accepted Qty' },
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<GoodsReceiptReportItem>({
        accessorKey: 'total_rejected_quantity',
        label: { key: 'column.rejected_qty', fallback: 'Rejected Qty' },
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<GoodsReceiptReportItem>({
        accessorKey: 'total_receipt_value',
        label: { key: 'column.total_value', fallback: 'Total Value' },
        locale: FORMAT_LOCALE,
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }),
];
