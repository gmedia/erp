'use client';

import { Badge } from '@/components/ui/badge';
import { type RecurringJournal } from '@/types/recurring-journal';
import {
    createActionsColumn,
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';

function getFrequencyLabel(frequency: RecurringJournal['frequency']) {
    const labels = {
        daily: 'Daily',
        weekly: 'Weekly',
        monthly: 'Monthly',
        quarterly: 'Quarterly',
        yearly: 'Yearly',
    };
    return labels[frequency] || frequency;
}

export const recurringJournalColumns: ColumnDef<RecurringJournal>[] = [
    createSelectColumn<RecurringJournal>(),
    createTextColumn<RecurringJournal>({
        accessorKey: 'name',
        label: 'Name',
        enableSorting: true,
    }),
    {
        accessorKey: 'frequency',
        ...createSortingHeader('Frequency'),
        cell: ({ row }) => getFrequencyLabel(row.getValue('frequency')),
    },
    {
        accessorKey: 'next_run_date',
        ...createSortingHeader('Next Run Date'),
        cell: ({ row }) =>
            formatDateByRegionalSettings(
                row.getValue('next_run_date') as string,
            ),
    },
    createRowCurrencyAmountColumn<
        RecurringJournal & { currency?: string | null }
    >({
        accessorKey: 'total_amount',
        label: 'Total Amount',
    }),
    {
        accessorKey: 'auto_post',
        ...createSortingHeader('Auto Post'),
        cell: ({ row }) => (
            <Badge
                variant={row.getValue('auto_post') ? 'default' : 'secondary'}
            >
                {row.getValue('auto_post') ? 'Yes' : 'No'}
            </Badge>
        ),
    },
    {
        accessorKey: 'is_active',
        ...createSortingHeader('Is Active'),
        cell: ({ row }) => (
            <Badge
                variant={row.getValue('is_active') ? 'default' : 'secondary'}
            >
                {row.getValue('is_active') ? 'Active' : 'Inactive'}
            </Badge>
        ),
    },
    {
        accessorKey: 'created_at',
        ...createSortingHeader('Created At'),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('created_at') as string),
    },
    createActionsColumn<RecurringJournal>(),
];
