'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { formatCurrencyByRegionalSettings } from '@/utils/number-format';
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
    references: { purchase_order_number: string | null; goods_receipt_number: string | null };
    amounts: { grand_total: string; amount_paid: string; amount_due: string };
    aging_buckets: { current: string; days_1_30: string; days_31_60: string; days_61_90: string; days_over_90: string };
};

function formatDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

function formatAmount(value: string | number | null | undefined): string {
    return formatCurrencyByRegionalSettings(value ?? 0, {
        locale: 'id-ID',
        currency: 'IDR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

export const apAgingReportColumns: ColumnDef<ApAgingReportItem>[] = [
    createReportSummaryColumn<ApAgingReportItem>({
        accessorKey: 'bill.number',
        header: 'Bill Number',
        getPrimary: (item) => item.bill?.number,
        getSecondary: (item) => formatDate(item.bill?.bill_date),
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'supplier.name',
        header: 'Supplier',
        getValue: (item) => item.supplier?.name,
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'branch.name',
        header: 'Branch',
        getValue: (item) => item.branch?.name,
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        accessorKey: 'bill.due_date',
        header: 'Due Date',
        getValue: (item) => formatDate(item.bill?.due_date),
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'grand_total',
        header: 'Grand Total',
        getValue: (item) => formatAmount(item.amounts?.grand_total),
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'amount_due',
        header: 'Amount Due',
        getValue: (item) => formatAmount(item.amounts?.amount_due),
        sortable: true,
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'current',
        header: 'Current',
        getValue: (item) => formatAmount(item.aging_buckets?.current),
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'days_1_30',
        header: '1-30 Days',
        getValue: (item) => formatAmount(item.aging_buckets?.days_1_30),
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'days_31_60',
        header: '31-60 Days',
        getValue: (item) => formatAmount(item.aging_buckets?.days_31_60),
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'days_61_90',
        header: '61-90 Days',
        getValue: (item) => formatAmount(item.aging_buckets?.days_61_90),
    }),
    createReportTextColumn<ApAgingReportItem>({
        id: 'days_over_90',
        header: '>90 Days',
        getValue: (item) => formatAmount(item.aging_buckets?.days_over_90),
    }),
    createReportStatusBadgeColumn<ApAgingReportItem>({
        accessorKey: 'bill.status',
        header: 'Status',
        getValue: (item) => item.bill?.status,
    }),
];
