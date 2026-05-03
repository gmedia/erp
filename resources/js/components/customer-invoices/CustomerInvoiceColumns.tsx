'use client';

import { Badge } from '@/components/ui/badge';
import { CustomerInvoice } from '@/types/customer-invoice';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';

const renderCustomerCell = ({
    row,
}: {
    row: { original: CustomerInvoice };
}) => <div>{row.original.customer?.name ?? '-'}</div>;

const renderBranchCell = ({ row }: { row: { original: CustomerInvoice } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderStatusCell = ({ row }: { row: { original: CustomerInvoice } }) => (
    <Badge variant="outline">{row.original.status.replace('_', ' ')}</Badge>
);

export const customerInvoiceColumns: ColumnDef<CustomerInvoice>[] = [
    createSelectColumn<CustomerInvoice>(),
    createTextColumn<CustomerInvoice>({
        accessorKey: 'invoice_number',
        label: 'Invoice Number',
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
    createDateColumn<CustomerInvoice>({
        accessorKey: 'invoice_date',
        label: 'Invoice Date',
    }),
    createDateColumn<CustomerInvoice>({
        accessorKey: 'due_date',
        label: 'Due Date',
    }),
    createTextColumn<CustomerInvoice>({
        accessorKey: 'currency',
        label: 'Currency',
    }),
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: renderStatusCell,
    },
    {
        accessorKey: 'grand_total',
        ...createSortingHeader('Grand Total'),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrencyByRegionalSettings(row.original.grand_total, {
                    locale: 'id-ID',
                    currency: row.original.currency || undefined,
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })}
            </div>
        ),
    },
    {
        accessorKey: 'amount_due',
        ...createSortingHeader('Amount Due'),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrencyByRegionalSettings(row.original.amount_due, {
                    locale: 'id-ID',
                    currency: row.original.currency || undefined,
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })}
            </div>
        ),
    },
    createActionsColumn<CustomerInvoice>(),
];
