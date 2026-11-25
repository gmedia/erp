'use client';

import {
    createAsyncSelectFilterField,
    createDateFilterFields,
    createSelectFilterFields,
    createTextFilterField,
    currencyOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export const apPaymentStatusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'pending_approval', label: 'Pending Approval' },
    { value: 'confirmed', label: 'Confirmed' },
    { value: 'reconciled', label: 'Reconciled' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'void', label: 'Void' },
];

export const apPaymentMethodOptions = [
    { value: 'bank_transfer', label: 'Bank Transfer' },
    { value: 'cash', label: 'Cash' },
    { value: 'check', label: 'Check' },
    { value: 'giro', label: 'Giro' },
    { value: 'other', label: 'Other' },
];

export function createApPaymentFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search payment number, reference, or notes...',
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
                options: apPaymentStatusOptions,
                placeholder: 'Select Status',
            },
            {
                name: 'payment_method',
                label: {
                    key: 'column.payment_method',
                    fallback: 'Payment Method',
                },
                options: apPaymentMethodOptions,
                placeholder: 'Select Payment Method',
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
                name: 'payment_date_from',
                label: {
                    key: 'column.payment_date_from',
                    fallback: 'Payment Date From',
                },
                placeholder: 'Payment Date From',
            },
            {
                name: 'payment_date_to',
                label: {
                    key: 'column.payment_date_to',
                    fallback: 'Payment Date To',
                },
                placeholder: 'Payment Date To',
            },
        ]),
    ];
}
