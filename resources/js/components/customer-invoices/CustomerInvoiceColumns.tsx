'use client';

import { CustomerInvoiceRowActions } from '@/components/customer-invoices/CustomerInvoiceRowActions';
import { CustomerInvoice } from '@/types/customer-invoice';
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
        label: { key: 'column.invoice_number', fallback: 'Invoice Number' },
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
    createDateColumn<CustomerInvoice>({
        accessorKey: 'invoice_date',
        label: { key: 'column.invoice_date', fallback: 'Invoice Date' },
    }),
    createDateColumn<CustomerInvoice>({
        accessorKey: 'due_date',
        label: { key: 'column.due_date', fallback: 'Due Date' },
    }),
    createTextColumn<CustomerInvoice>({
        accessorKey: 'currency',
        label: { key: 'column.currency', fallback: 'Currency' },
    }),
    createStatusBadgeColumn<CustomerInvoice>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    createRowCurrencyAmountColumn<CustomerInvoice>({
        accessorKey: 'grand_total',
        label: { key: 'column.grand_total', fallback: 'Grand Total' },
    }),
    createRowCurrencyAmountColumn<CustomerInvoice>({
        accessorKey: 'amount_due',
        label: { key: 'column.amount_due', fallback: 'Amount Due' },
    }),
    {
        id: 'actions_custom',
        enableHiding: false,
        size: 56,
        cell: ({ row, table }) => {
            const meta = table.options.meta as
                | CustomTableMeta<CustomerInvoice>
                | undefined;
            return (
                <CustomerInvoiceRowActions
                    item={row.original}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
