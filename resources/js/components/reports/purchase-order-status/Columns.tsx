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

export type PurchaseOrderStatusReportItem = {
    purchase_order: {
        id: number;
        po_number: string | null;
        order_date: string | null;
        expected_delivery_date: string | null;
        status: string;
        status_category:
            | 'not_started'
            | 'outstanding'
            | 'partially_received'
            | 'closed'
            | 'cancelled';
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
    ordered_quantity: string;
    received_quantity: string;
    outstanding_quantity: string;
    receipt_progress_percent: string;
    grand_total: string;
};

function formatDate(value: string | null | undefined): string {
    return formatDateValue(value);
}

export const purchaseOrderStatusReportColumns: ColumnDef<PurchaseOrderStatusReportItem>[] =
    [
        createReportSummaryColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'purchase_order.po_number',
            header: { key: 'column.po_number', fallback: 'PO Number' },
            getPrimary: (item) => item.purchase_order?.po_number,
            getSecondary: (item) => formatDate(item.purchase_order?.order_date),
            sortable: true,
        }),
        createReportTextColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'supplier.name',
            header: { key: 'column.supplier', fallback: 'Supplier' },
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportWarehouseColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'warehouse.name',
            header: { key: 'column.warehouse', fallback: 'Warehouse' },
            getWarehouse: (item) => item.warehouse,
            sortable: true,
        }),
        createReportStatusBadgeColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'purchase_order.status',
            header: { key: 'column.status', fallback: 'Status' },
            getValue: (item) => item.purchase_order?.status,
        }),
        createReportStatusBadgeColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'purchase_order.status_category',
            header: {
                key: 'column.status_category',
                fallback: 'Status Category',
            },
            getValue: (item) => item.purchase_order?.status_category,
        }),
        createNumberColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'ordered_quantity',
            label: { key: 'column.ordered_qty', fallback: 'Ordered Qty' },
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'received_quantity',
            label: { key: 'column.received_qty', fallback: 'Received Qty' },
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'outstanding_quantity',
            label: {
                key: 'column.outstanding_qty',
                fallback: 'Outstanding Qty',
            },
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'receipt_progress_percent',
            label: {
                key: 'column.receipt_progress',
                fallback: 'Receipt Progress (%)',
            },
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'grand_total',
            label: { key: 'column.grand_total', fallback: 'Grand Total' },
            locale: FORMAT_LOCALE,
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        }),
        createReportTextColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'purchase_order.expected_delivery_date',
            header: {
                key: 'column.expected_delivery',
                fallback: 'Expected Delivery',
            },
            getValue: (item) =>
                formatDate(item.purchase_order?.expected_delivery_date),
            sortable: true,
        }),
    ];
