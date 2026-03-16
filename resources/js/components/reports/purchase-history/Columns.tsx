'use client';

import { Badge } from '@/components/ui/badge';
import {
    createCurrencyColumn,
    createNumberColumn,
    createSortingHeader,
} from '@/utils/columns';
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
    if (!value) return '-';
    return new Date(value).toLocaleDateString();
}

function formatLabel(value: string | null | undefined): string {
    if (!value) return '-';
    return value.replaceAll('_', ' ');
}

export const purchaseHistoryReportColumns: ColumnDef<PurchaseHistoryReportItem>[] =
    [
        {
            accessorKey: 'purchase_order.po_number',
            ...createSortingHeader('PO Number'),
            cell: ({ row }) => (
                <div className="space-y-0.5">
                    <div className="font-medium">
                        {row.original.purchase_order?.po_number ?? '-'}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {formatDate(row.original.purchase_order?.order_date)}
                    </div>
                </div>
            ),
        },
        {
            accessorKey: 'supplier.name',
            ...createSortingHeader('Supplier'),
            cell: ({ row }) => <div>{row.original.supplier?.name ?? '-'}</div>,
        },
        {
            accessorKey: 'product.name',
            ...createSortingHeader('Product'),
            cell: ({ row }) => (
                <div className="space-y-0.5">
                    <div className="font-medium">
                        {row.original.product?.name ?? '-'}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {row.original.product?.code ?? '-'}
                    </div>
                </div>
            ),
        },
        {
            accessorKey: 'warehouse.name',
            ...createSortingHeader('Warehouse'),
            cell: ({ row }) => (
                <div className="space-y-0.5">
                    <div className="font-medium">
                        {row.original.warehouse?.name ?? '-'}
                    </div>
                    <div className="text-xs text-muted-foreground">
                        {row.original.warehouse?.code ?? '-'}
                    </div>
                </div>
            ),
        },
        {
            accessorKey: 'purchase_order.status',
            ...createSortingHeader('Status'),
            cell: ({ row }) => (
                <Badge variant="outline" className="capitalize">
                    {formatLabel(row.original.purchase_order?.status)}
                </Badge>
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
                <div>{formatDate(row.original.last_receipt_date)}</div>
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
                <div>
                    {formatDate(
                        row.original.purchase_order?.expected_delivery_date,
                    )}
                </div>
            ),
        },
    ];
