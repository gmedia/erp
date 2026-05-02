'use client';

import { Badge } from '@/components/ui/badge';
import { ApPayment } from '@/types/ap-payment';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';

const renderSupplierCell = ({ row }: { row: { original: ApPayment } }) => (
    <div>{row.original.supplier?.name ?? '-'}</div>
);

const renderBranchCell = ({ row }: { row: { original: ApPayment } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderPaymentMethodCell = ({ row }: { row: { original: ApPayment } }) => (
    <Badge variant="outline">{row.original.payment_method.replace('_', ' ')}</Badge>
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
                {formatCurrencyByRegionalSettings(row.original.total_unallocated, {
                    locale: 'id-ID',
                    currency: row.original.currency || undefined,
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })}
            </div>
        ),
    },
    createActionsColumn<ApPayment>(),
];