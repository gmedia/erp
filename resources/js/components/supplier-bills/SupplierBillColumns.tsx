'use client';

import { SupplierBillRowActions } from '@/components/supplier-bills/SupplierBillRowActions';
import { SupplierBill } from '@/types/supplier-bill';
import {
    type CustomTableMeta,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
} from '@/utils/columns';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';

const renderSupplierCell = ({ row }: { row: { original: SupplierBill } }) => (
    <div>{row.original.supplier?.name ?? '-'}</div>
);

const renderBranchCell = ({ row }: { row: { original: SupplierBill } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

export const supplierBillColumns: ColumnDef<SupplierBill>[] = [
    createSelectColumn<SupplierBill>(),
    createTextColumn<SupplierBill>({
        accessorKey: 'bill_number',
        label: { key: 'column.bill_number', fallback: 'Bill Number' },
        primary: true,
    }),
    {
        accessorKey: 'supplier',
        ...createSortingHeader({
            key: 'column.supplier',
            fallback: 'Supplier',
        }),
        cell: renderSupplierCell,
    },
    {
        accessorKey: 'branch',
        ...createSortingHeader({ key: 'column.branch', fallback: 'Branch' }),
        cell: renderBranchCell,
    },
    createDateColumn<SupplierBill>({
        accessorKey: 'bill_date',
        label: { key: 'column.bill_date', fallback: 'Bill Date' },
    }),
    createDateColumn<SupplierBill>({
        accessorKey: 'due_date',
        label: { key: 'column.due_date', fallback: 'Due Date' },
    }),
    createTextColumn<SupplierBill>({
        accessorKey: 'currency',
        label: { key: 'column.currency', fallback: 'Currency' },
    }),
    createStatusBadgeColumn<SupplierBill>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    {
        accessorKey: 'grand_total',
        ...createSortingHeader({
            key: 'column.grand_total',
            fallback: 'Grand Total',
        }),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrencyByRegionalSettings(row.original.grand_total, {
                    locale: 'id-ID',
                    currency: row.original.currency || undefined,
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                })}
            </div>
        ),
    },
    {
        accessorKey: 'amount_due',
        ...createSortingHeader({
            key: 'column.amount_due',
            fallback: 'Amount Due',
        }),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrencyByRegionalSettings(row.original.amount_due, {
                    locale: 'id-ID',
                    currency: row.original.currency || undefined,
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                })}
            </div>
        ),
    },
    {
        id: 'actions_custom',
        enableHiding: false,
        size: 56,
        cell: ({ row, table }) => {
            const meta = table.options.meta as
                | CustomTableMeta<SupplierBill>
                | undefined;
            return (
                <SupplierBillRowActions
                    item={row.original}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
