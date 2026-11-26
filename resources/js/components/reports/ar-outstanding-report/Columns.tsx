'use client';

import { createNumberColumn } from '@/utils/columns';
import { FORMAT_LOCALE } from '@/utils/formatters';
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
        createNumberColumn<ArOutstandingReportItem>({
            accessorKey: 'days_overdue',
            label: 'Days Overdue',
            locale: FORMAT_LOCALE,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
            className: 'text-right tabular-nums',
        }),
    ];
