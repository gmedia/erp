'use client';

import {
    createRowCurrencyAmountColumn,
    createSortingHeader,
    createTextColumn,
} from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
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

type GLItemWithCurrency = GeneralLedgerItem & { currency?: string | null };

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
    createRowCurrencyAmountColumn<GLItemWithCurrency>({
        accessorKey: 'debit',
        label: 'Debit',
    }),
    createRowCurrencyAmountColumn<GLItemWithCurrency>({
        accessorKey: 'credit',
        label: 'Credit',
    }),
    createRowCurrencyAmountColumn<GLItemWithCurrency>({
        accessorKey: 'running_balance',
        label: 'Running Balance',
    }),
];
