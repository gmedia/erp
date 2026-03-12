'use client';

import { Badge } from '@/components/ui/badge';
import {
    createCurrencyColumn,
    createNumberColumn,
    createSortingHeader,
} from '@/utils/columns';
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
    if (!value) return '-';
    return new Date(value).toLocaleDateString();
}

function formatLabel(value: string | null | undefined): string {
    if (!value) return '-';
    return value.replace(/_/g, ' ');
}

export const goodsReceiptReportColumns: ColumnDef<GoodsReceiptReportItem>[] = [
    {
        accessorKey: 'goods_receipt.gr_number',
        ...createSortingHeader('GR Number'),
        cell: ({ row }) => (
            <div className="space-y-0.5">
                <div className="font-medium">
                    {row.original.goods_receipt?.gr_number ?? '-'}
                </div>
                <div className="text-xs text-muted-foreground">
                    {formatDate(row.original.goods_receipt?.receipt_date)}
                </div>
            </div>
        ),
    },
    {
        accessorKey: 'purchase_order.po_number',
        ...createSortingHeader('PO Number'),
        cell: ({ row }) => (
            <div>{row.original.purchase_order?.po_number ?? '-'}</div>
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
        accessorKey: 'goods_receipt.status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => (
            <Badge variant="outline" className="capitalize">
                {formatLabel(row.original.goods_receipt?.status)}
            </Badge>
        ),
    },
    createNumberColumn<GoodsReceiptReportItem>({
        accessorKey: 'item_count',
        label: 'Item Count',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }),
    createNumberColumn<GoodsReceiptReportItem>({
        accessorKey: 'total_received_quantity',
        label: 'Received Qty',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<GoodsReceiptReportItem>({
        accessorKey: 'total_accepted_quantity',
        label: 'Accepted Qty',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createNumberColumn<GoodsReceiptReportItem>({
        accessorKey: 'total_rejected_quantity',
        label: 'Rejected Qty',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createCurrencyColumn<GoodsReceiptReportItem>({
        accessorKey: 'total_receipt_value',
        label: 'Total Value',
        currency: 'IDR',
        locale: 'id-ID',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
];
