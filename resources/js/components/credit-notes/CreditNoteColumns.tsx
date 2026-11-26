'use client';

import { CreditNoteRowActions } from '@/components/credit-notes/CreditNoteRowActions';
import { Badge } from '@/components/ui/badge';
import { formatStatusLabel } from '@/lib/status-badge';
import { CreditNote } from '@/types/credit-note';
import {
    type CustomTableMeta,
    createDateColumn,
    createSelectColumn,
    createSortingHeader,
    createStatusBadgeColumn,
    createTextColumn,
} from '@/utils/columns';
import { FORMAT_LOCALE } from '@/utils/formatters';
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
        label: {
            key: 'column.credit_note_number',
            fallback: 'Credit Note Number',
        },
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
    createDateColumn<CreditNote>({
        accessorKey: 'credit_note_date',
        label: {
            key: 'column.credit_note_date',
            fallback: 'Credit Note Date',
        },
    }),
    {
        accessorKey: 'reason',
        ...createSortingHeader({ key: 'column.reason', fallback: 'Reason' }),
        cell: renderReasonCell,
    },
    createStatusBadgeColumn<CreditNote>({
        accessorKey: 'status',
        label: { key: 'column.status', fallback: 'Status' },
    }),
    {
        accessorKey: 'grand_total',
        meta: { numeric: true },
        ...createSortingHeader(
            {
                key: 'column.grand_total',
                fallback: 'Grand Total',
            },
            { align: 'right' },
        ),
        cell: ({ row }) => (
            <div className="text-right">
                {formatCurrencyByRegionalSettings(row.original.grand_total, {
                    locale: FORMAT_LOCALE,
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
                | CustomTableMeta<CreditNote>
                | undefined;
            return (
                <CreditNoteRowActions
                    item={row.original}
                    onView={meta?.onView}
                    onEdit={meta?.onEdit}
                    onDelete={meta?.onDelete}
                />
            );
        },
    },
];
