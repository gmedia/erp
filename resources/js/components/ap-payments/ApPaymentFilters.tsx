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
    { value: 'draft', label: { key: 'status.draft', fallback: 'Draft' } },
    {
        value: 'pending_approval',
        label: {
            key: 'status.pending_approval',
            fallback: 'Pending Approval',
        },
    },
    {
        value: 'confirmed',
        label: { key: 'status.confirmed', fallback: 'Confirmed' },
    },
    {
        value: 'reconciled',
        label: { key: 'status.reconciled', fallback: 'Reconciled' },
    },
    {
        value: 'cancelled',
        label: { key: 'status.cancelled', fallback: 'Cancelled' },
    },
    { value: 'void', label: { key: 'status.void', fallback: 'Void' } },
];

export const apPaymentMethodOptions = [
    {
        value: 'bank_transfer',
        label: {
            key: 'filter.payment_method_bank_transfer',
            fallback: 'Bank Transfer',
        },
    },
    {
        value: 'cash',
        label: { key: 'filter.payment_method_cash', fallback: 'Cash' },
    },
    {
        value: 'check',
        label: { key: 'filter.payment_method_check', fallback: 'Check' },
    },
    {
        value: 'giro',
        label: { key: 'filter.payment_method_giro', fallback: 'Giro' },
    },
    {
        value: 'other',
        label: { key: 'filter.payment_method_other', fallback: 'Other' },
    },
];

export function createApPaymentFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.ap_payments.search_placeholder',
                fallback: 'Search payment number, reference, or notes...',
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
                options: apPaymentStatusOptions,
                placeholder: {
                    key: 'filter.select_status',
                    fallback: 'Select Status',
                },
            },
            {
                name: 'payment_method',
                label: {
                    key: 'column.payment_method',
                    fallback: 'Payment Method',
                },
                options: apPaymentMethodOptions,
                placeholder: {
                    key: 'filter.select_payment_method',
                    fallback: 'Select Payment Method',
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
                name: 'payment_date_from',
                label: {
                    key: 'column.payment_date_from',
                    fallback: 'Payment Date From',
                },
                placeholder: {
                    key: 'column.payment_date_from',
                    fallback: 'Payment Date From',
                },
            },
            {
                name: 'payment_date_to',
                label: {
                    key: 'column.payment_date_to',
                    fallback: 'Payment Date To',
                },
                placeholder: {
                    key: 'column.payment_date_to',
                    fallback: 'Payment Date To',
                },
            },
        ]),
    ];
}
