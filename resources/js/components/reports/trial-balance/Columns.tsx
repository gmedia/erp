'use client';

import { createSortingHeader, createTextColumn } from '@/utils/columns';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
import { type ColumnDef } from '@tanstack/react-table';

export interface TrialBalanceItem {
    account_code: string;
    account_name: string;
    account_type: string;
    debit_balance: number;
    credit_balance: number;
}

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
    {
        accessorKey: 'debit_balance',
        ...createSortingHeader('Debit Balance'),
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('debit_balance'));
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
        accessorKey: 'credit_balance',
        ...createSortingHeader('Credit Balance'),
        cell: ({ row }) => {
            const amount = Number.parseFloat(row.getValue('credit_balance'));
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
