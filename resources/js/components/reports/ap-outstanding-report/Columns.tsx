'use client';

import {
    createReportStatusBadgeColumn,
    createReportSummaryColumn,
    createReportTextColumn,
} from '@/components/common/ReportColumns';
import { createCurrencyColumn, createNumberColumn } from '@/utils/columns';
import { formatDateByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export type ApOutstandingReportItem = {
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
    supplier: {
        id: number;
        name: string;
    };
    branch: {
        id: number;
        name: string;
    };
    references: {
        purchase_order_number: string | null;
        goods_receipt_number: string | null;
    };
    amounts: {
        grand_total: string;
        amount_paid: string;
        amount_due: string;
    };
    days_overdue: number;
};

function formatDate(value: string | null | undefined): string {
    return formatDateByRegionalSettings(value);
}

export const apOutstandingReportColumns: ColumnDef<ApOutstandingReportItem>[] =
    [
        createReportSummaryColumn<ApOutstandingReportItem>({
            id: 'col',
            header: 'Bill Number',
            getPrimary: (item) => item.bill?.number,
            getSecondary: (item) => formatDate(item.bill?.bill_date),
            sortable: true,
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            id: 'col',
            header: 'Supplier',
            getValue: (item) => item.supplier?.name,
            sortable: true,
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            id: 'col',
            header: 'Branch',
            getValue: (item) => item.branch?.name,
            sortable: true,
        }),
        createReportTextColumn<ApOutstandingReportItem>({
            id: 'col',
            header: 'Due Date',
            getValue: (item) => formatDate(item.bill?.due_date),
            sortable: true,
        }),
        createCurrencyColumn<ApOutstandingReportItem>({
            id: 'col',
            label: 'Grand Total',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<ApOutstandingReportItem>({
            id: 'col',
            label: 'Amount Paid',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createCurrencyColumn<ApOutstandingReportItem>({
            id: 'col',
            label: 'Amount Due',
            currency: 'IDR',
            locale: 'id-ID',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }),
        createNumberColumn<ApOutstandingReportItem>({
            accessorKey: 'days_overdue',
            label: 'Days Overdue',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }),
        createReportStatusBadgeColumn<ApOutstandingReportItem>({
            id: 'status',
            header: 'Status',
            getValue: (item) => item.bill?.status,
        }),
    ];
