'use client';

import { formatNumberByRegionalSettings } from '@/utils/number-format';
import type { ColumnDef } from '@tanstack/react-table';
import {
    type ArInvoiceReportBase,
    createArInvoiceBaseColumns,
} from '../ar-report-columns';

export type ArOutstandingReportItem = ArInvoiceReportBase & {
    days_overdue: number;
};

export const arOutstandingReportColumns: ColumnDef<ArOutstandingReportItem>[] =
    [
        ...createArInvoiceBaseColumns<ArOutstandingReportItem>(),
        {
            id: 'days_overdue',
            accessorKey: 'days_overdue',
            header: 'Days Overdue',
            cell: ({ row }) => {
                const value = row.original.days_overdue;
                if (value === null || value === undefined) return '-';
                return formatNumberByRegionalSettings(value, {
                    locale: 'id-ID',
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0,
                });
            },
        },
    ];
