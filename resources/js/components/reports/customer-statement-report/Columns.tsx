'use client';

import type { ColumnDef } from '@tanstack/react-table';
import {
    type ArInvoiceReportBase,
    createArInvoiceBaseColumns,
    formatArReportCurrency,
} from '../ar-report-columns';

export type CustomerStatementReportItem = ArInvoiceReportBase & {
    running_balance: string;
};

export const customerStatementReportColumns: ColumnDef<CustomerStatementReportItem>[] =
    [
        ...createArInvoiceBaseColumns<CustomerStatementReportItem>(),
        {
            id: 'running_balance',
            accessorKey: 'running_balance',
            header: 'Running Balance',
            cell: ({ row }) =>
                formatArReportCurrency(row.original.running_balance),
        },
    ];
