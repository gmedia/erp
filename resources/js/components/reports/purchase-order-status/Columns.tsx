'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
    createReportWarehouseColumn,
} from '@/components/common/ReportColumns';
import {
    createCurrencyColumn,
    createNumberColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type PurchaseOrderStatusReportItem = {
    purchase_order: {
        id: number;
        po_number: string | null;
        order_date: string | null;
        expected_delivery_date: string | null;
        status: string;
        status_category: 'outstanding' | 'partially_received' | 'closed';
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
    return formatDateByRegionalSettings(value);
}

export const purchaseOrderStatusReportColumns: ColumnDef<PurchaseOrderStatusReportItem>[] =
    [
        createReportSummaryColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'purchase_order.po_number',
            header: 'PO Number',
            getPrimary: (item) => item.purchase_order?.po_number,
            getSecondary: (item) => formatDate(item.purchase_order?.order_date),
            sortable: true,
        }),
        createReportTextColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'supplier.name',
            header: 'Supplier',
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportWarehouseColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'warehouse.name',
            header: 'Warehouse',
            getWarehouse: (item) => item.warehouse,
            sortable: true,
        }),
        createReportStatusBadgeColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'purchase_order.status',
            header: 'Status',
            getValue: (item) => item.purchase_order?.status,
        }),
        createReportStatusBadgeColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'purchase_order.status_category',
            header: 'Status Category',
            getValue: (item) => item.purchase_order?.status_category,
            getVariant: (item) =>
                item.purchase_order?.status_category === 'closed'
                    ? 'default'
                    : 'outline',
        }),
        createNumberColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'ordered_quantity',
            label: 'Ordered Qty',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'received_quantity',
            label: 'Received Qty',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'outstanding_quantity',
            label: 'Outstanding Qty',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'receipt_progress_percent',
            label: 'Receipt Progress (%)',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'grand_total',
            label: 'Grand Total',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createReportTextColumn<PurchaseOrderStatusReportItem>({
            accessorKey: 'purchase_order.expected_delivery_date',
            header: 'Expected Delivery',
            getValue: (item) =>
                formatDate(item.purchase_order?.expected_delivery_date),
            sortable: true,
        }),
    ];
