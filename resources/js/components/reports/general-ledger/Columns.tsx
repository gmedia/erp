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

const glLabel = (column: string, fallback: string) => ({
    key: `page.general_ledger.columns.${column}`,
    fallback,
});

export const generalLedgerColumns: ColumnDef<GeneralLedgerItem>[] = [
    {
        accessorKey: 'entry_date',
        ...createSortingHeader(glLabel('date', 'Date')),
        cell: ({ row }) =>
            formatDateByRegionalSettings(row.getValue('entry_date') as string),
    },
    createTextColumn<GeneralLedgerItem>({
        accessorKey: 'entry_number',
        label: glLabel('entry_number', 'Entry Number'),
        enableSorting: true,
    }),
    createTextColumn<GeneralLedgerItem>({
        accessorKey: 'description',
        label: glLabel('description', 'Description'),
    }),
    createTextColumn<GeneralLedgerItem>({
        accessorKey: 'reference',
        label: glLabel('reference', 'Reference'),
    }),
    createRowCurrencyAmountColumn<GLItemWithCurrency>({
        accessorKey: 'debit',
        label: glLabel('debit', 'Debit'),
    }),
    createRowCurrencyAmountColumn<GLItemWithCurrency>({
        accessorKey: 'credit',
        label: glLabel('credit', 'Credit'),
    }),
    createRowCurrencyAmountColumn<GLItemWithCurrency>({
        accessorKey: 'running_balance',
        label: glLabel('running_balance', 'Running Balance'),
    }),
];
