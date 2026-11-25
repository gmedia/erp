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
        label: 'Bill Number',
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
    createDateColumn<SupplierBill>({
        accessorKey: 'bill_date',
        label: 'Bill Date',
    }),
    createDateColumn<SupplierBill>({
        accessorKey: 'due_date',
        label: 'Due Date',
    }),
    createTextColumn<SupplierBill>({
        accessorKey: 'currency',
        label: 'Currency',
    }),
    createStatusBadgeColumn<SupplierBill>({
        accessorKey: 'status',
        label: 'Status',
    }),
    {
        accessorKey: 'grand_total',
        ...createSortingHeader('Grand Total'),
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
        ...createSortingHeader('Amount Due'),
        cell: ({ row }) => {
            const { status, amount_due: amountDue, currency } = row.original;
            const hasNoDue = ['draft', 'void', 'cancelled'].includes(
                status ?? '',
            );

            return (
                <div
                    className={`text-right ${Number(amountDue) < 0 ? 'text-destructive' : ''}`}
                >
                    {hasNoDue
                        ? '-'
                        : formatCurrencyByRegionalSettings(amountDue, {
                              locale: 'id-ID',
                              currency: currency || undefined,
                              minimumFractionDigits: 0,
                              maximumFractionDigits: 2,
                          })}
                </div>
            );
        },
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
