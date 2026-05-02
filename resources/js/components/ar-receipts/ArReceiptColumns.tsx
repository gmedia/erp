'use client';

import { Badge } from '@/components/ui/badge';
import { ArReceipt } from '@/types/ar-receipt';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';

const renderCustomerCell = ({ row }: { row: { original: ArReceipt } }) => (
    <div>{row.original.customer?.name ?? '-'}</div>
);

const renderBranchCell = ({ row }: { row: { original: ArReceipt } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderPaymentMethodCell = ({ row }: { row: { original: ArReceipt } }) => (
    <Badge variant="outline">
        {row.original.payment_method.replace('_', ' ')}
    </Badge>
);

const renderStatusCell = ({ row }: { row: { original: ArReceipt } }) => (
    <Badge variant="outline">{row.original.status.replace('_', ' ')}</Badge>
);

export const arReceiptColumns: ColumnDef<ArReceipt>[] = [
    createSelectColumn<ArReceipt>(),
    createTextColumn<ArReceipt>({
        accessorKey: 'receipt_number',
        label: 'Receipt Number',
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
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    {
        accessorKey: 'total_amount',
        ...createSortingHeader('Total Amount'),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrencyByRegionalSettings(row.original.total_amount, {
                    locale: 'id-ID',
                    currency: row.original.currency || undefined,
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })}
            </div>
        ),
    },
    {
        accessorKey: 'total_unallocated',
        ...createSortingHeader('Unallocated'),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrencyByRegionalSettings(
                    row.original.total_unallocated,
                    {
                        locale: 'id-ID',
                        currency: row.original.currency || undefined,
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    },
                )}
            </div>
        ),
    },
    createActionsColumn<ArReceipt>(),
];
