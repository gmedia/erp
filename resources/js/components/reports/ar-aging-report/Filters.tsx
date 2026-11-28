'use client';

import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';

const customerInvoiceStatusOptions: SelectOption[] = [
    { label: { key: 'status.draft', fallback: 'Draft' }, value: 'draft' },
    { label: { key: 'status.sent', fallback: 'Sent' }, value: 'sent' },
    {
        label: { key: 'status.partially_paid', fallback: 'Partially Paid' },
        value: 'partially_paid',
    },
    { label: { key: 'status.paid', fallback: 'Paid' }, value: 'paid' },
    { label: { key: 'status.overdue', fallback: 'Overdue' }, value: 'overdue' },
    {
        label: { key: 'status.cancelled', fallback: 'Cancelled' },
        value: 'cancelled',
    },
    { label: { key: 'status.void', fallback: 'Void' }, value: 'void' },
];

export function createArAgingReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.ar_aging.search_placeholder',
                fallback: 'Search invoice number, customer, or branch...',
            },
        ),
        createAsyncSelectFilterField(
            'customer_id',
            { key: 'column.customer', fallback: 'Customer' },
            '/api/customers',
            { key: 'filter.select_customer', fallback: 'Select customer' },
        ),
        createAsyncSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            '/api/branches',
            { key: 'filter.select_branch_short', fallback: 'Select branch' },
        ),
        createSelectFilterField(
            'status',
            { key: 'filter_field.status', fallback: 'Status' },
            customerInvoiceStatusOptions,
            { key: 'filter.select_status', fallback: 'Select status' },
        ),
        ...createDateRangeFilterFields(
            { key: 'column.start_date', fallback: 'Start Date' },
            { key: 'column.end_date', fallback: 'End Date' },
        ),
    ];
}
