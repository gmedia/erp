'use client';

import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { CreditNote } from '@/types/credit-note';
import {
    createActionsColumn,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
} from '@/utils/columns';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { ColumnDef } from '@tanstack/react-table';

const renderCustomerCell = ({ row }: { row: { original: CreditNote } }) => (
    <div>{row.original.customer?.name ?? '-'}</div>
);

const renderBranchCell = ({ row }: { row: { original: CreditNote } }) => (
    <div>{row.original.branch?.name ?? '-'}</div>
);

const renderReasonCell = ({ row }: { row: { original: CreditNote } }) => (
    <Badge variant="outline">{formatStatusLabel(row.original.reason)}</Badge>
);

export const creditNoteColumns: ColumnDef<CreditNote>[] = [
    createSelectColumn<CreditNote>(),
    createTextColumn<CreditNote>({
        accessorKey: 'credit_note_number',
        label: 'Credit Note Number',
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
    createDateColumn<CreditNote>({
        accessorKey: 'credit_note_date',
        label: 'Credit Note Date',
    }),
    {
        accessorKey: 'reason',
        ...createSortingHeader('Reason'),
        cell: renderReasonCell,
    },
    createStatusBadgeColumn<CreditNote>({
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
                    currency: 'IDR',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 2,
                })}
            </div>
        ),
    },
    createActionsColumn<CreditNote>(),
];
