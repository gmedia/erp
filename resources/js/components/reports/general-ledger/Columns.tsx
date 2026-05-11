'use client';

import { createSortingHeader, createTextColumn } from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { type ColumnDef } from '@tanstack/react-table';

export interface GeneralLedgerItem {
    entry_date: string;
    entry_number: string;
    description: string;
    reference?: string;
    debit: number;
    credit: number;
    running_balance: number;
}

export const generalLedgerColumns: ColumnDef<GeneralLedgerItem>[] = [
    {
        accessorKey: 'entry_date',
        ...createSortingHeader('Date'),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('entry_date') as string),
    },
    createTextColumn<GeneralLedgerItem>({
        accessorKey: 'entry_number',
        label: 'Entry Number',
        enableSorting: true,
    }),
    createTextColumn<GeneralLedgerItem>({
        accessorKey: 'description',
        label: 'Description',
    }),
    createTextColumn<GeneralLedgerItem>({
        accessorKey: 'reference',
        label: 'Reference',
    }),
    {
        accessorKey: 'debit',
        ...createSortingHeader('Debit'),
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('debit'));
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
        accessorKey: 'credit',
        ...createSortingHeader('Credit'),
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('credit'));
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
        accessorKey: 'running_balance',
        ...createSortingHeader('Running Balance'),
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('running_balance'));
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
];
