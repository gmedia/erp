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

export type PurchaseHistoryReportItem = {
    id: number;
    purchase_order: {
        id: number;
        po_number: string | null;
        order_date: string | null;
        expected_delivery_date: string | null;
        status: string;
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
    product: {
        id: number;
        code: string | null;
        name: string;
    };
    ordered_quantity: string;
    received_quantity: string;
    outstanding_quantity: string;
    receipt_count: number;
    last_receipt_date: string | null;
    total_purchase_value: string;
};

function formatDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

export const purchaseHistoryReportColumns: ColumnDef<PurchaseHistoryReportItem>[] =
    [
        createReportSummaryColumn<PurchaseHistoryReportItem>({
            accessorKey: 'purchase_order.po_number',
            header: 'PO Number',
            getPrimary: (item) => item.purchase_order?.po_number,
            getSecondary: (item) => formatDate(item.purchase_order?.order_date),
            sortable: true,
        }),
        createReportTextColumn<PurchaseHistoryReportItem>({
            accessorKey: 'supplier.name',
            header: 'Supplier',
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportSummaryColumn<PurchaseHistoryReportItem>({
            accessorKey: 'product.name',
            header: 'Product',
            getPrimary: (item) => item.product?.name,
            getSecondary: (item) => item.product?.code,
            sortable: true,
        }),
        createReportWarehouseColumn<PurchaseHistoryReportItem>({
            accessorKey: 'warehouse.name',
            header: 'Warehouse',
            getWarehouse: (item) => item.warehouse,
            sortable: true,
        }),
        createReportStatusBadgeColumn<PurchaseHistoryReportItem>({
            accessorKey: 'purchase_order.status',
            header: 'Status',
            getValue: (item) => item.purchase_order?.status,
        }),
        createNumberColumn<PurchaseHistoryReportItem>({
            accessorKey: 'ordered_quantity',
            label: 'Ordered Qty',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseHistoryReportItem>({
            accessorKey: 'received_quantity',
            label: 'Received Qty',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseHistoryReportItem>({
            accessorKey: 'outstanding_quantity',
            label: 'Outstanding Qty',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<PurchaseHistoryReportItem>({
            accessorKey: 'receipt_count',
            label: 'Receipt Count',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }),
        createReportTextColumn<PurchaseHistoryReportItem>({
            accessorKey: 'goods_receipt.last_receipt_date',
            header: 'Last Receipt',
            getValue: (item) => formatDate(item.last_receipt_date),
            sortable: true,
        }),
        createCurrencyColumn<PurchaseHistoryReportItem>({
            accessorKey: 'total_purchase_value',
            label: 'Total Value',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createReportTextColumn<PurchaseHistoryReportItem>({
            accessorKey: 'purchase_order.expected_delivery_date',
            header: 'Expected Delivery',
            getValue: (item) =>
                formatDate(item.purchase_order?.expected_delivery_date),
            sortable: true,
        }),
    ];
