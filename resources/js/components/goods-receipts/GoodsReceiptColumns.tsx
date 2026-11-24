'use client';

import { GoodsReceiptRowActions } from '@/components/goods-receipts/GoodsReceiptRowActions';
import { GoodsReceipt } from '@/types/goods-receipt';
import {
    type CustomTableMeta,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const renderPoNumberCell = ({ row }: { row: { original: GoodsReceipt } }) => (
    <div>{row.original.purchase_order?.po_number ?? '-'}</div>
);

const renderWarehouseCell = ({ row }: { row: { original: GoodsReceipt } }) => (
    <div>{row.original.warehouse?.name ?? '-'}</div>
);

export const goodsReceiptColumns: ColumnDef<GoodsReceipt>[] = [
    createSelectColumn<GoodsReceipt>(),
    createTextColumn<GoodsReceipt>({
        accessorKey: 'gr_number',
        label: 'GR Number',
        primary: true,
    }),
    {
        accessorKey: 'purchase_order',
        ...createSortingHeader('PO Number'),
        cell: renderPoNumberCell,
    },
    {
        accessorKey: 'warehouse',
        ...createSortingHeader('Warehouse'),
        cell: renderWarehouseCell,
    },
    createDateColumn<GoodsReceipt>({
        accessorKey: 'receipt_date',
        label: 'Receipt Date',
    }),
    createTextColumn<GoodsReceipt>({
        accessorKey: 'supplier_delivery_note',
        label: 'Supplier Delivery Note',
    }),
    createStatusBadgeColumn<GoodsReceipt>({
        accessorKey: 'status',
        label: 'Status',
    }),
    {
        id: 'actions_custom',
        enableHiding: false,
        size: 56,
        cell: ({ row, table }) => {
            const meta = table.options.meta as
                | CustomTableMeta<GoodsReceipt>
                | undefined;
            return (
                <GoodsReceiptRowActions
                    item={row.original}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
