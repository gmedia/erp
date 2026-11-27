'use client';

import {
    createAsyncSelectFilterField,
    createDateFilterFields,
    createSelectFilterFields,
    createTextFilterField,
    currencyOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export const supplierBillStatusOptions = [
    { value: 'draft', label: { key: 'status.draft', fallback: 'Draft' } },
    {
        value: 'confirmed',
        label: { key: 'status.confirmed', fallback: 'Confirmed' },
    },
    {
        value: 'partially_paid',
        label: { key: 'status.partially_paid', fallback: 'Partially Paid' },
    },
    { value: 'paid', label: { key: 'status.paid', fallback: 'Paid' } },
    {
        value: 'overdue',
        label: { key: 'status.overdue', fallback: 'Overdue' },
    },
    {
        value: 'cancelled',
        label: { key: 'status.cancelled', fallback: 'Cancelled' },
    },
    { value: 'void', label: { key: 'status.void', fallback: 'Void' } },
];

export function createSupplierBillFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.supplier_bills.search_placeholder',
                fallback:
                    'Search bill number, supplier invoice number, or notes...',
            },
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            { key: 'column.supplier', fallback: 'Supplier' },
            '/api/suppliers',
            { key: 'filter.all_suppliers', fallback: 'All suppliers' },
        ),
        createAsyncSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            '/api/branches',
            { key: 'filter.all_branches', fallback: 'All branches' },
        ),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: supplierBillStatusOptions,
                placeholder: {
                    key: 'filter.select_status',
                    fallback: 'Select Status',
                },
            },
            {
                name: 'currency',
                label: { key: 'column.currency', fallback: 'Currency' },
                options: currencyOptions,
                placeholder: {
                    key: 'filter.select_currency',
                    fallback: 'Select Currency',
                },
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'bill_date_from',
                label: {
                    key: 'column.bill_date_from',
                    fallback: 'Bill Date From',
                },
                placeholder: {
                    key: 'column.bill_date_from',
                    fallback: 'Bill Date From',
                },
            },
            {
                name: 'bill_date_to',
                label: { key: 'column.bill_date_to', fallback: 'Bill Date To' },
                placeholder: {
                    key: 'column.bill_date_to',
                    fallback: 'Bill Date To',
                },
            },
            {
                name: 'due_date_from',
                label: {
                    key: 'column.due_date_from',
                    fallback: 'Due Date From',
                },
                placeholder: {
                    key: 'column.due_date_from',
                    fallback: 'Due Date From',
                },
            },
            {
                name: 'due_date_to',
                label: { key: 'column.due_date_to', fallback: 'Due Date To' },
                placeholder: {
                    key: 'column.due_date_to',
                    fallback: 'Due Date To',
                },
            },
        ]),
    ];
}
