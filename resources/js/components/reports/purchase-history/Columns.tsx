'use client';

import {
    StatusBadgeCell,
    SummaryCell,
    TextCell,
    WarehouseSummaryCell,
} from '@/components/common/ReportColumns';
import {
    createCurrencyColumn,
    createNumberColumn,
    createSortingHeader,
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
            accessorKey: 'product.name',
            ...createSortingHeader('Product'),
            cell: ({ row }) => (
                <SummaryCell
                    primary={row.original.product?.name}
                    secondary={row.original.product?.code}
                />
            ),
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
        {
            accessorKey: 'goods_receipt.last_receipt_date',
            ...createSortingHeader('Last Receipt'),
            cell: ({ row }) => (
                <TextCell value={formatDate(row.original.last_receipt_date)} />
            ),
        },
        createCurrencyColumn<PurchaseHistoryReportItem>({
            accessorKey: 'total_purchase_value',
            label: 'Total Value',
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
