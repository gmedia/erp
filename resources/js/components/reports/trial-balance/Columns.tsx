'use client';

import { createRowCurrencyAmountColumn, createTextColumn } from '@/utils/columns';
import { type ColumnDef } from '@tanstack/react-table';

export interface TrialBalanceItem {
    account_code: string;
    account_name: string;
    account_type: string;
    debit_balance: number;
    credit_balance: number;
}

type TBItemWithCurrency = TrialBalanceItem & { currency?: string | null };

export const trialBalanceColumns: ColumnDef<TrialBalanceItem>[] = [
    createTextColumn<TrialBalanceItem>({
        accessorKey: 'account_code',
        label: 'Account Code',
        enableSorting: true,
    }),
    createTextColumn<TrialBalanceItem>({
        accessorKey: 'account_name',
        label: 'Account Name',
        enableSorting: true,
    }),
    createTextColumn<TrialBalanceItem>({
        accessorKey: 'account_type',
        label: 'Account Type',
        enableSorting: true,
    }),
    createRowCurrencyAmountColumn<TBItemWithCurrency>({
        accessorKey: 'debit_balance',
        label: 'Debit Balance',
    }),
    createRowCurrencyAmountColumn<TBItemWithCurrency>({
        accessorKey: 'credit_balance',
        label: 'Credit Balance',
    }),
];
