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
    return formatDateByRegionalSettings(value);
}

export const goodsReceiptReportColumns: ColumnDef<GoodsReceiptReportItem>[] = [
    {
        accessorKey: 'goods_receipt.gr_number',
        ...createSortingHeader('GR Number'),
        cell: ({ row }) => (
            <SummaryCell
                primary={row.original.goods_receipt?.gr_number}
                secondary={formatDate(row.original.goods_receipt?.receipt_date)}
            />
        ),
    },
    {
        accessorKey: 'purchase_order.po_number',
        ...createSortingHeader('PO Number'),
        cell: ({ row }) => (
            <TextCell value={row.original.purchase_order?.po_number} />
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
        accessorKey: 'goods_receipt.status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => (
            <StatusBadgeCell value={row.original.goods_receipt?.status} />
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
