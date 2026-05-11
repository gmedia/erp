'use client';

import { Badge } from '@/components/ui/badge';
import { ApPayment } from '@/types/ap-payment';
import {
    createActionsColumn,
    createDateColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const renderSupplierCell = ({ row }: { row: { original: ApPayment } }) => (
    <div>{row.original.supplier?.name ?? '-'}</div>
);

const renderBranchCell = ({ row }: { row: { original: ApPayment } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderPaymentMethodCell = ({ row }: { row: { original: ApPayment } }) => (
    <Badge variant="outline">
        {row.original.payment_method.replace('_', ' ')}
    </Badge>
);

const renderStatusCell = ({ row }: { row: { original: ApPayment } }) => (
    <Badge variant="outline">{row.original.status.replace('_', ' ')}</Badge>
);

export const apPaymentColumns: ColumnDef<ApPayment>[] = [
    createSelectColumn<ApPayment>(),
    createTextColumn<ApPayment>({
        accessorKey: 'payment_number',
        label: 'Payment Number',
    }),
    {
        accessorKey: 'supplier',
        ...createSortingHeader('Supplier'),
        cell: renderSupplierCell,
    },
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    createDateColumn<ApPayment>({
        accessorKey: 'payment_date',
        label: 'Payment Date',
    }),
    {
        accessorKey: 'payment_method',
        ...createSortingHeader('Payment Method'),
        cell: renderPaymentMethodCell,
    },
    createTextColumn<ApPayment>({
        accessorKey: 'currency',
        label: 'Currency',
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    createRowCurrencyAmountColumn<ApPayment>({
        accessorKey: 'total_amount',
        label: 'Total Amount',
    }),
    createRowCurrencyAmountColumn<ApPayment>({
        accessorKey: 'total_unallocated',
        label: 'Unallocated',
    }),
    createActionsColumn<ApPayment>(),
];
