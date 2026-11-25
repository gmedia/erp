'use client';

import { GenericActions } from '@/components/common/ActionsDropdown';
import { Badge } from '@/components/ui/badge';
import { formatStatusLabel, statusBadgeVariant } from '@/lib/status-badge';
import { type JournalEntry } from '@/types/journal-entry';
import {
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
    type CustomTableMeta,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { type ColumnDef } from '@tanstack/react-table';

export const journalEntryColumns: ColumnDef<JournalEntry>[] = [
    createSelectColumn<JournalEntry>(),
    createTextColumn<JournalEntry>({
        accessorKey: 'entry_number',
        label: { key: 'column.entry_number', fallback: 'Entry Number' },
        primary: true,
        enableSorting: true,
    }),
    {
        accessorKey: 'entry_date',
        ...createSortingHeader({ key: 'column.date', fallback: 'Date' }),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('entry_date') as string),
    },
    createTextColumn<JournalEntry>({
        accessorKey: 'description',
        label: { key: 'column.description', fallback: 'Description' },
    }),
    createTextColumn<JournalEntry>({
        accessorKey: 'reference',
        label: { key: 'column.reference', fallback: 'Reference' },
    }),
    {
        accessorKey: 'total_debit',
        ...createSortingHeader({
            key: 'column.total_amount',
            fallback: 'Total Amount',
        }),
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('total_debit'));
            return (
                <div className="text-right font-medium">
                    {formatCurrencyByRegionalSettings(amount, {
                        locale: 'id-ID',
                        currency: 'IDR',
                    })}
                </div>
            );
        },
    },
    {
        accessorKey: 'status',
        ...createSortingHeader({ key: 'column.status', fallback: 'Status' }),
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            return (
                <Badge variant={statusBadgeVariant(status)}>
                    {formatStatusLabel(status)}
                </Badge>
            );
        },
    },
    {
        id: 'actions',
        size: 56,
        cell: ({ row, table }) => {
            const meta = table.options.meta as CustomTableMeta<JournalEntry>;
            const item = row.original;
            const isDraft = item.status === 'draft';
            return (
                <GenericActions
                    item={item}
                    onView={meta?.onView}
                    onEdit={isDraft ? meta?.onEdit : undefined}
                    onDelete={isDraft ? meta?.onDelete : undefined}
                />
            );
        },
    },
];
