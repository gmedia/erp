'use client';

import { Badge } from '@/components/ui/badge';
import { SupplierReturn } from '@/types/supplier-return';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const renderPoNumberCell = ({ row }: { row: { original: SupplierReturn } }) => (
    <div>{row.original.purchase_order?.po_number ?? '-'}</div>
);

const renderGrNumberCell = ({ row }: { row: { original: SupplierReturn } }) => (
    <div>{row.original.goods_receipt?.gr_number ?? '-'}</div>
);

const renderSupplierCell = ({ row }: { row: { original: SupplierReturn } }) => (
    <div>{row.original.supplier?.name ?? '-'}</div>
);

const renderWarehouseCell = ({
    row,
}: {
    row: { original: SupplierReturn };
}) => <div>{row.original.warehouse?.name ?? '-'}</div>;

const renderStatusCell = ({ row }: { row: { original: SupplierReturn } }) => (
    <Badge variant="outline">{row.original.status}</Badge>
);

export const supplierReturnColumns: ColumnDef<SupplierReturn>[] = [
    createSelectColumn<SupplierReturn>(),
    createTextColumn<SupplierReturn>({
        accessorKey: 'return_number',
        label: 'Return Number',
    }),
    {
        accessorKey: 'purchase_order',
        ...createSortingHeader('PO Number'),
        cell: renderPoNumberCell,
    },
    {
        accessorKey: 'goods_receipt',
        ...createSortingHeader('GR Number'),
        cell: renderGrNumberCell,
    },
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
    createDateColumn<SupplierReturn>({
        accessorKey: 'return_date',
        label: 'Return Date',
    }),
    {
        accessorKey: 'reason',
        ...createSortingHeader('Reason'),
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createActionsColumn<SupplierReturn>(),
];
