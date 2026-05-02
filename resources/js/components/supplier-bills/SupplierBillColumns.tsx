'use client';

import { Badge } from '@/components/ui/badge';
import { SupplierBill } from '@/types/supplier-bill';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
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

const renderStatusCell = ({ row }: { row: { original: SupplierBill } }) => (
    <Badge variant="outline">{row.original.status.replace('_', ' ')}</Badge>
);

export const supplierBillColumns: ColumnDef<SupplierBill>[] = [
    createSelectColumn<SupplierBill>(),
    createTextColumn<SupplierBill>({
        accessorKey: 'bill_number',
        label: 'Bill Number',
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
    createActionsColumn<SupplierBill>(),
];