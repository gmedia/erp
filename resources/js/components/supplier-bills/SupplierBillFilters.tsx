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
    { value: 'draft', label: 'Draft' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'partially_paid', label: 'Partially Paid' },
    { value: 'paid', label: 'Paid' },
    { value: 'overdue', label: 'Overdue' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'void', label: 'Void' },
];

export function createSupplierBillFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search bill number, supplier invoice number, or notes...',
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            { key: 'column.supplier', fallback: 'Supplier' },
            '/api/suppliers',
            'All suppliers',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            { key: 'column.branch', fallback: 'Branch' },
            '/api/branches',
            'All branches',
        ),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: supplierBillStatusOptions,
                placeholder: 'Select Status',
            },
            {
                name: 'currency',
                label: { key: 'column.currency', fallback: 'Currency' },
                options: currencyOptions,
                placeholder: 'Select Currency',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'bill_date_from',
                label: {
                    key: 'column.bill_date_from',
                    fallback: 'Bill Date From',
                },
                placeholder: 'Bill Date From',
            },
            {
                name: 'bill_date_to',
                label: { key: 'column.bill_date_to', fallback: 'Bill Date To' },
                placeholder: 'Bill Date To',
            },
            {
                name: 'due_date_from',
                label: {
                    key: 'column.due_date_from',
                    fallback: 'Due Date From',
                },
                placeholder: 'Due Date From',
            },
            {
                name: 'due_date_to',
                label: { key: 'column.due_date_to', fallback: 'Due Date To' },
                placeholder: 'Due Date To',
            },
        ]),
    ];
}
