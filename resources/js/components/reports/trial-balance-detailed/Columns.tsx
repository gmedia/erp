'use client';

import { createReportTextColumn } from '@/components/common/ReportColumns';
import { formatStatusLabel } from '@/lib/status-badge';
import { createCurrencyColumn } from '@/utils/columns';
import { FORMAT_LOCALE } from '@/utils/formatters';
import type { ColumnDef } from '@tanstack/react-table';

export type TrialBalanceDetailedItem = {
    account_id: number;
    account_code: string;
    account_name: string;
    account_type: string;
    opening_balance: string;
    debit_total: string;
    credit_total: string;
    closing_balance: string;
    debit_balance: string;
    credit_balance: string;
};

const currencyCol = (
    accessorKey: keyof TrialBalanceDetailedItem,
    label: string,
) =>
    createCurrencyColumn<TrialBalanceDetailedItem>({
        accessorKey,
        label,
        locale: FORMAT_LOCALE,
        className: 'text-right',
    });

export const trialBalanceDetailedColumns: ColumnDef<TrialBalanceDetailedItem>[] =
    [
        createReportTextColumn<TrialBalanceDetailedItem>({
            id: 'account_code',
            header: 'Account Code',
            getValue: (item) => item.account_code,
            sortable: true,
        }),
        createReportTextColumn<TrialBalanceDetailedItem>({
            id: 'account_name',
            header: 'Account Name',
            getValue: (item) => item.account_name,
            sortable: true,
        }),
        createReportTextColumn<TrialBalanceDetailedItem>({
            id: 'account_type',
            header: 'Type',
            getValue: (item) => formatStatusLabel(item.account_type),
            sortable: true,
        }),
        currencyCol('opening_balance', 'Opening Balance'),
        currencyCol('debit_total', 'Debit'),
        currencyCol('credit_total', 'Credit'),
        currencyCol('closing_balance', 'Closing Balance'),
        currencyCol('debit_balance', 'Debit Balance'),
        currencyCol('credit_balance', 'Credit Balance'),
    ];
