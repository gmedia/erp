'use client';

import { StatusBadgeLabel } from '@/components/common/StatusBadgeLabel';
import { PurchaseOrder } from '@/types/purchase-order';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';

const renderSupplierCell = ({ row }: { row: { original: PurchaseOrder } }) => (
    <div>{row.original.supplier?.name ?? '-'}</div>
);

const renderWarehouseCell = ({ row }: { row: { original: PurchaseOrder } }) => (
    <div>{row.original.warehouse?.name ?? '-'}</div>
);

const renderStatusCell = ({ row }: { row: { original: PurchaseOrder } }) => (
    <StatusBadgeLabel status={row.original.status} />
);

export const purchaseOrderColumns: ColumnDef<PurchaseOrder>[] = [
    createSelectColumn<PurchaseOrder>(),
    createTextColumn<PurchaseOrder>({
        accessorKey: 'po_number',
        label: { key: 'column.po_number', fallback: 'PO Number' },
        primary: true,
        description: (row) => row.supplier?.name ?? null,
    }),
    {
        accessorKey: 'supplier',
        ...createSortingHeader({
            key: 'column.supplier',
            fallback: 'Supplier',
        }),
        cell: renderSupplierCell,
    },
    {
        accessorKey: 'warehouse',
        ...createSortingHeader({
            key: 'column.warehouse',
            fallback: 'Warehouse',
        }),
        cell: renderWarehouseCell,
    },
    createDateColumn<PurchaseOrder>({
        accessorKey: 'order_date',
        label: { key: 'column.order_date', fallback: 'Order Date' },
    }),
    createDateColumn<PurchaseOrder>({
        accessorKey: 'expected_delivery_date',
        label: {
            key: 'column.expected_delivery',
            fallback: 'Expected Delivery',
        },
    }),
    createTextColumn<PurchaseOrder>({
        accessorKey: 'currency',
        label: { key: 'column.currency', fallback: 'Currency' },
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: renderStatusCell,
    },
    {
        accessorKey: 'grand_total',
        ...createSortingHeader({
            key: 'column.grand_total',
            fallback: 'Grand Total',
        }),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrencyByRegionalSettings(row.original.grand_total, {
                    locale: 'id-ID',
                    currency: row.original.currency || undefined,
                })}
            </div>
        ),
    },
    createActionsColumn<PurchaseOrder>(),
];
