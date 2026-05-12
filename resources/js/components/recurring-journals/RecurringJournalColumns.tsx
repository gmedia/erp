'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { type RecurringJournal } from '@/types/recurring-journal';
import {
    createRowCurrencyAmountColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
    type CustomTableMeta,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, MoreHorizontal, Pencil, Play, Trash } from 'lucide-react';

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
    createRowCurrencyAmountColumn<RecurringJournal & { currency?: string | null }>({
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
    {
        id: 'row-actions',
        cell: ({ row, table }) => {
            const meta = table.options
                .meta as CustomTableMeta<RecurringJournal>;
            return (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" className="h-8 w-8 p-0">
                            <span className="sr-only">Open menu</span>
                            <MoreHorizontal className="h-4 w-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem
                            onClick={() => meta?.onView?.(row.original)}
                        >
                            <Eye className="mr-2 h-4 w-4" />
                            View
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => meta?.onEdit?.(row.original)}
                        >
                            <Pencil className="mr-2 h-4 w-4" />
                            Edit
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => {
                                const customAction = meta?.onCustomAction as
                                    | ((
                                          action: string,
                                          item: RecurringJournal,
                                      ) => void)
                                    | undefined;
                                if (customAction) {
                                    customAction('execute', row.original);
                                }
                            }}
                        >
                            <Play className="mr-2 h-4 w-4" />
                            Execute
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onClick={() => meta?.onDelete?.(row.original)}
                            className="text-red-600"
                        >
                            <Trash className="mr-2 h-4 w-4" />
                            Delete
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            );
        },
    },
];
