'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { formatCurrency } from '@/lib/utils';
import { createNumberColumn } from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type ApOutstandingReportItem = {
    id: number;
    bill: {
        number: string;
        bill_date: string | null;
        due_date: string | null;
        status: string;
        currency: string;
    };
    supplier: { id: number; name: string };
    branch: { id: number; name: string };
    amounts: { grand_total: string; amount_paid: string; amount_due: string };
    days_overdue: number;
};

function formatDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

function formatAmount(value: string | number | null | undefined): string {
    return formatCurrency(value);
}

export const apOutstandingReportColumns: ColumnDef<ApOutstandingReportItem>[] =
    [
        createReportSummaryColumn<ApOutstandingReportItem>({
            accessorKey: 'bill.number',
            header: { key: 'column.bill_number', fallback: 'Bill Number' },
            getPrimary: (item) => item.bill?.number,
            getSecondary: (item) => formatDate(item.bill?.bill_date),
            sortable: true,
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            accessorKey: 'supplier.name',
            header: { key: 'column.supplier', fallback: 'Supplier' },
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            accessorKey: 'branch.name',
            header: { key: 'column.branch', fallback: 'Branch' },
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            accessorKey: 'bill.due_date',
            header: { key: 'column.due_date', fallback: 'Due Date' },
            getValue: (item) => formatDate(item.bill?.due_date),
            sortable: true,
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            id: 'grand_total',
            header: { key: 'column.grand_total', fallback: 'Grand Total' },
            getValue: (item) => formatAmount(item.amounts?.grand_total),
            sortable: true,
            align: 'right',
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            id: 'amount_paid',
            header: { key: 'column.amount_paid', fallback: 'Amount Paid' },
            getValue: (item) => formatAmount(item.amounts?.amount_paid),
            sortable: true,
            align: 'right',
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            id: 'amount_due',
            header: { key: 'column.amount_due', fallback: 'Amount Due' },
            getValue: (item) => formatAmount(item.amounts?.amount_due),
            sortable: true,
            align: 'right',
        }),
        createNumberColumn<ApOutstandingReportItem>({
            accessorKey: 'days_overdue',
            label: 'Days Overdue',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }),
        createReportStatusBadgeColumn<ApOutstandingReportItem>({
            accessorKey: 'bill.status',
            header: { key: 'column.status', fallback: 'Status' },
            getValue: (item) => item.bill?.status,
        }),
    ];
