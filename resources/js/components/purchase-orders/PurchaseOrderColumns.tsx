'use client';

import { Badge } from '@/components/ui/badge';
import { PurchaseOrder } from '@/types/purchase-order';
import {
    createActionsColumn,
    createDateColumn,
    createNumberColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const renderSupplierCell = ({ row }: { row: { original: PurchaseOrder } }) => (
    <div>{row.original.supplier?.name ?? '-'}</div>
);

const renderWarehouseCell = ({ row }: { row: { original: PurchaseOrder } }) => (
    <div>{row.original.warehouse?.name ?? '-'}</div>
);

const renderStatusCell = ({ row }: { row: { original: PurchaseOrder } }) => (
    <Badge variant="outline">{row.original.status.replace('_', ' ')}</Badge>
);

export const purchaseOrderColumns: ColumnDef<PurchaseOrder>[] = [
    createSelectColumn<PurchaseOrder>(),
    createTextColumn<PurchaseOrder>({
        accessorKey: 'po_number',
        label: 'PO Number',
    }),
    {
        accessorKey: 'supplier',
        ...createSortingHeader('Supplier'),
        cell: renderSupplierCell,
    },
    {
        accessorKey: 'warehouse',
        ...createSortingHeader('Warehouse'),
        cell: renderWarehouseCell,
    },
    createDateColumn<PurchaseOrder>({
        accessorKey: 'order_date',
        label: 'Order Date',
    }),
    createDateColumn<PurchaseOrder>({
        accessorKey: 'expected_delivery_date',
        label: 'Expected Delivery',
    }),
    createTextColumn<PurchaseOrder>({
        accessorKey: 'currency',
        label: 'Currency',
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createNumberColumn<PurchaseOrder>({
        accessorKey: 'grand_total',
        label: 'Grand Total',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }),
    createActionsColumn<PurchaseOrder>(),
];
