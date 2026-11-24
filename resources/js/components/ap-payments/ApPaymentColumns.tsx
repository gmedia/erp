'use client';

import { ApPaymentRowActions } from '@/components/ap-payments/ApPaymentRowActions';
import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { ApPayment } from '@/types/ap-payment';
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

const renderSupplierCell = ({ row }: { row: { original: ApPayment } }) => (
    <div>{row.original.supplier?.name ?? '-'}</div>
);

const renderBranchCell = ({ row }: { row: { original: ApPayment } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderPaymentMethodCell = ({ row }: { row: { original: ApPayment } }) => (
    <Badge variant="outline">
        {formatStatusLabel(row.original.payment_method)}
    </Badge>
);

export const apPaymentColumns: ColumnDef<ApPayment>[] = [
    createSelectColumn<ApPayment>(),
    createTextColumn<ApPayment>({
        accessorKey: 'payment_number',
        label: 'Payment Number',
        primary: true,
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
    createStatusBadgeColumn<ApPayment>({
        accessorKey: 'status',
        label: 'Status',
        variantMap: { confirmed: 'success' },
    }),
    createRowCurrencyAmountColumn<ApPayment>({
        accessorKey: 'total_amount',
        label: 'Total Amount',
    }),
    createRowCurrencyAmountColumn<ApPayment>({
        accessorKey: 'total_unallocated',
        label: 'Unallocated',
    }),
    {
        id: 'actions_custom',
        enableHiding: false,
        size: 56,
        cell: ({ row, table }) => {
            const meta = table.options.meta as
                | CustomTableMeta<ApPayment>
                | undefined;
            return (
                <ApPaymentRowActions
                    item={row.original}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
