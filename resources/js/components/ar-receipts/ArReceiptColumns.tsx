'use client';

import { ArReceiptRowActions } from '@/components/ar-receipts/ArReceiptRowActions';
import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { ArReceipt } from '@/types/ar-receipt';
import {
    type CustomTableMeta,
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
        label: { key: 'column.receipt_number', fallback: 'Receipt Number' },
        primary: true,
    }),
    {
        accessorKey: 'customer',
        ...createSortingHeader({
            key: 'column.customer',
            fallback: 'Customer',
        }),
        cell: renderCustomerCell,
    },
    {
        accessorKey: 'branch',
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: renderBranchCell,
    },
    createDateColumn<ArReceipt>({
        accessorKey: 'receipt_date',
        label: { key: 'column.receipt_date', fallback: 'Receipt Date' },
    }),
    {
        accessorKey: 'payment_method',
        ...createSortingHeader({
            key: 'column.payment_method',
            fallback: 'Payment Method',
        }),
        cell: renderPaymentMethodCell,
    },
    createTextColumn<ArReceipt>({
        accessorKey: 'currency',
        label: { key: 'column.currency', fallback: 'Currency' },
    }),
    createStatusBadgeColumn<ArReceipt>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    createRowCurrencyAmountColumn<ArReceipt>({
        accessorKey: 'total_amount',
        label: { key: 'column.total_amount', fallback: 'Total Amount' },
    }),
    createRowCurrencyAmountColumn<ArReceipt>({
        accessorKey: 'total_unallocated',
        label: { key: 'column.unallocated', fallback: 'Unallocated' },
    }),
    {
        id: 'actions_custom',
        enableHiding: false,
        size: 56,
        cell: ({ row, table }) => {
            const meta = table.options.meta as
                | CustomTableMeta<ArReceipt>
                | undefined;
            return (
                <ArReceiptRowActions
                    item={row.original}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
