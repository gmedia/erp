'use client';

import { Badge } from '@/components/ui/badge';
import {
    createCurrencyColumn,
    createNumberColumn,
    createSortingHeader,
} from '@/utils/columns';
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
    if (!value) return '-';
    return new Date(value).toLocaleDateString();
}

function formatLabel(value: string | null | undefined): string {
    if (!value) return '-';
    return value.replaceAll('_', ' ');
}

export const purchaseOrderStatusReportColumns: ColumnDef<PurchaseOrderStatusReportItem>[] =
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
        {
            accessorKey: 'purchase_order.status_category',
            ...createSortingHeader('Status Category'),
            cell: ({ row }) => {
                const value = row.original.purchase_order?.status_category;
                const variant = value === 'closed' ? 'default' : 'outline';
                return (
                    <Badge variant={variant} className="capitalize">
                        {formatLabel(value)}
                    </Badge>
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
                <div>
                    {formatDate(
                        row.original.purchase_order?.expected_delivery_date,
                    )}
                </div>
            ),
        },
    ];
