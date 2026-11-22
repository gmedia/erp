'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { ArReceipt } from '@/types/ar-receipt';
import {
    createActionsColumn,
    createDateColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
} from '@/utils/columns';
import { ColumnDef } from '@tanstack/react-table';

const renderCustomerCell = ({ row }: { row: { original: ArReceipt } }) => (
    <div>{row.original.customer?.name ?? '-'}</div>
);

const renderBranchCell = ({ row }: { row: { original: ArReceipt } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderPaymentMethodCell = ({ row }: { row: { original: ArReceipt } }) => (
    <Badge variant="outline">
        {formatStatusLabel(row.original.payment_method)}
    </Badge>
);

export const arReceiptColumns: ColumnDef<ArReceipt>[] = [
    createSelectColumn<ArReceipt>(),
    createTextColumn<ArReceipt>({
        accessorKey: 'receipt_number',
        label: 'Receipt Number',
        primary: true,
    }),
    {
        accessorKey: 'customer',
        ...createSortingHeader('Customer'),
        cell: renderCustomerCell,
    },
    {
        accessorKey: 'branch',
        ...createSortingHeader('Branch'),
        cell: renderBranchCell,
    },
    createDateColumn<ArReceipt>({
        accessorKey: 'receipt_date',
        label: 'Receipt Date',
    }),
    {
        accessorKey: 'payment_method',
        ...createSortingHeader('Payment Method'),
        cell: renderPaymentMethodCell,
    },
    createTextColumn<ArReceipt>({
        accessorKey: 'currency',
        label: 'Currency',
    }),
    createStatusBadgeColumn<ArReceipt>({
        accessorKey: 'status',
        label: 'Status',
    }),
    createRowCurrencyAmountColumn<ArReceipt>({
        accessorKey: 'total_amount',
        label: 'Total Amount',
    }),
    createRowCurrencyAmountColumn<ArReceipt>({
        accessorKey: 'total_unallocated',
        label: 'Unallocated',
    }),
    createActionsColumn<ArReceipt>(),
];
