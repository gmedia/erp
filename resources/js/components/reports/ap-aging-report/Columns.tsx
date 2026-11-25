'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { formatCurrency } from '@/lib/utils';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type ApAgingReportItem = {
    id: number;
    bill: {
        number: string;
        supplier_invoice_number: string | null;
        bill_date: string | null;
        due_date: string | null;
        status: string;
        currency: string;
        payment_terms: string | null;
        notes: string | null;
    };
    supplier: { id: number; name: string };
    branch: { id: number; name: string };
    references: {
        purchase_order_number: string | null;
        goods_receipt_number: string | null;
    };
    amounts: { grand_total: string; amount_paid: string; amount_due: string };
    aging_buckets: {
        current: string;
        days_1_30: string;
        days_31_60: string;
        days_61_90: string;
        days_over_90: string;
    };
};

function formatDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

function formatAmount(value: string | number | null | undefined): string {
    return formatCurrency(value);
}

export const apAgingReportColumns: ColumnDef<ApAgingReportItem>[] = [
    createReportSummaryColumn<ApAgingReportItem>({
        accessorKey: 'bill.number',
        header: { key: 'column.bill_number', fallback: 'Bill Number' },
        getPrimary: (item) => item.bill?.number,
        getSecondary: (item) => formatDate(item.bill?.bill_date),
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'supplier.name',
        header: { key: 'column.supplier', fallback: 'Supplier' },
        getValue: (item) => item.supplier?.name,
        className: 'max-w-40 sm:max-w-[12rem]',
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'branch.name',
        header: { key: 'column.branch', fallback: 'Branch' },
        getValue: (item) => item.branch?.name,
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'bill.due_date',
        header: { key: 'column.due_date', fallback: 'Due Date' },
        getValue: (item) => formatDate(item.bill?.due_date),
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'grand_total',
        header: { key: 'column.grand_total', fallback: 'Grand Total' },
        getValue: (item) => formatAmount(item.amounts?.grand_total),
        sortable: true,
        align: 'right',
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'amount_due',
        header: { key: 'column.amount_due', fallback: 'Amount Due' },
        getValue: (item) => formatAmount(item.amounts?.amount_due),
        sortable: true,
        align: 'right',
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'current',
        header: { key: 'column.current', fallback: 'Current' },
        getValue: (item) => formatAmount(item.aging_buckets?.current),
        align: 'right',
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'days_1_30',
        header: { key: 'column.days_1_30', fallback: '1-30 Days' },
        getValue: (item) => formatAmount(item.aging_buckets?.days_1_30),
        align: 'right',
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'days_31_60',
        header: { key: 'column.days_31_60', fallback: '31-60 Days' },
        getValue: (item) => formatAmount(item.aging_buckets?.days_31_60),
        align: 'right',
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'days_61_90',
        header: { key: 'column.days_61_90', fallback: '61-90 Days' },
        getValue: (item) => formatAmount(item.aging_buckets?.days_61_90),
        align: 'right',
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'days_over_90',
        header: { key: 'column.days_over_90', fallback: 'Over 90 Days' },
        getValue: (item) => formatAmount(item.aging_buckets?.days_over_90),
        align: 'right',
    }),
    createReportStatusBadgeColumn<ApAgingReportItem>({
        accessorKey: 'bill.status',
        header: { key: 'column.status', fallback: 'Status' },
        getValue: (item) => item.bill?.status,
    }),
];
