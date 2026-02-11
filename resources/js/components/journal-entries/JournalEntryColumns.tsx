'use client';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type JournalEntry } from '@/types/journal-entry';
import {
    createActionsColumn,
    createSelectColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { type ColumnDef } from '@tanstack/react-table';
import { Eye, Pencil, Trash } from 'lucide-react';

export const journalEntryColumns: ColumnDef<JournalEntry>[] = [
    createSelectColumn<JournalEntry>(),
    createTextColumn<JournalEntry>({
        accessorKey: 'entry_number',
        label: 'Entry Number',
        enableSorting: true,
    }),
    {
        accessorKey: 'entry_date',
        ...createSortingHeader('Date'),
        cell: ({ row }) => {
            const date = new Date(row.getValue('entry_date'));
            return new Intl.DateTimeFormat('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
            }).format(date);
        },
    },
    createTextColumn<JournalEntry>({
        accessorKey: 'description',
        label: 'Description',
    }),
    createTextColumn<JournalEntry>({
        accessorKey: 'reference',
        label: 'Reference',
    }),
    {
        accessorKey: 'total_debit',
        ...createSortingHeader('Total Amount'),
        cell: ({ row }) => {
            const amount = parseFloat(row.getValue('total_debit'));
            return (
                <div className="text-right font-medium">
                    {new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                    }).format(amount)}
                </div>
            );
        },
    },
    {
        accessorKey: 'status',
        ...createSortingHeader('Status'),
        cell: ({ row }) => {
            const status = row.getValue('status') as string;
            return (
                <Badge
                    variant={
                        status === 'posted'
                            ? 'default'
                            : status === 'draft'
                              ? 'secondary'
                              : 'destructive'
                    }
                >
                    {status.toUpperCase()}
                </Badge>
            );
        },
    },
    {
        id: 'row-actions',
        cell: ({ row, table }) => {
            const meta = table.options.meta as any;
            return (
                <div className="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => meta?.onView?.(row.original)}
                    >
                        <Eye className="h-4 w-4" />
                    </Button>
                    {row.original.status === 'draft' && (
                        <>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => meta?.onEdit?.(row.original)}
                            >
                                <Pencil className="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => meta?.onDelete?.(row.original)}
                            >
                                <Trash className="h-4 w-4 text-red-500" />
                            </Button>
                        </>
                    )}
                </div>
            );
        },
    },
];
