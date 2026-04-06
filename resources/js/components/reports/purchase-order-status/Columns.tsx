'use client';

import {
    StatusBadgeCell,
    SummaryCell,
    TextCell,
    WarehouseSummaryCell,
    formatReportLabel,
} from '@/components/common/ReportColumns';
import {
    createCurrencyColumn,
    createNumberColumn,
    createSortingHeader,
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
        {
            accessorKey: 'purchase_order.po_number',
            ...createSortingHeader('PO Number'),
            cell: ({ row }) => (
                <SummaryCell
                    primary={row.original.purchase_order?.po_number}
                    secondary={formatDate(row.original.purchase_order?.order_date)}
                />
            ),
        },
        {
            accessorKey: 'supplier.name',
            ...createSortingHeader('Supplier'),
            cell: ({ row }) => <TextCell value={row.original.supplier?.name} />,
        },
        {
            accessorKey: 'warehouse.name',
            ...createSortingHeader('Warehouse'),
            cell: ({ row }) => (
                <WarehouseSummaryCell warehouse={row.original.warehouse} />
            ),
        },
        {
            accessorKey: 'purchase_order.status',
            ...createSortingHeader('Status'),
            cell: ({ row }) => (
                <StatusBadgeCell value={row.original.purchase_order?.status} />
            ),
        },
        {
            accessorKey: 'purchase_order.status_category',
            ...createSortingHeader('Status Category'),
            cell: ({ row }) => {
                const value = row.original.purchase_order?.status_category;
                const variant = value === 'closed' ? 'default' : 'outline';
                return (
                    <StatusBadgeCell value={value} variant={variant} />
                );
            },
        },
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
        {
            accessorKey: 'purchase_order.expected_delivery_date',
            ...createSortingHeader('Expected Delivery'),
            cell: ({ row }) => (
                <TextCell
                    value={formatDate(
                        row.original.purchase_order?.expected_delivery_date,
                    )}
                />
            ),
        },
    ];
