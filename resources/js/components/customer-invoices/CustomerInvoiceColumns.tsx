'use client';

import { CustomerInvoice } from '@/types/customer-invoice';
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

const renderCustomerCell = ({
    row,
}: {
    row: { original: CustomerInvoice };
}) => <div>{row.original.customer?.name ?? '-'}</div>;

const renderBranchCell = ({ row }: { row: { original: CustomerInvoice } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

export const customerInvoiceColumns: ColumnDef<CustomerInvoice>[] = [
    createSelectColumn<CustomerInvoice>(),
    createTextColumn<CustomerInvoice>({
        accessorKey: 'invoice_number',
        label: 'Invoice Number',
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
    createStatusBadgeColumn<CustomerInvoice>({
        accessorKey: 'status',
        label: 'Status',
    }),
    createRowCurrencyAmountColumn<CustomerInvoice>({
        accessorKey: 'grand_total',
        label: 'Grand Total',
    }),
    createRowCurrencyAmountColumn<CustomerInvoice>({
        accessorKey: 'amount_due',
        label: 'Amount Due',
    }),
    createActionsColumn<CustomerInvoice>(),
];
