'use client';

import {
    createAsyncSelectFilterField,
    createColumnDateFilterConfig,
    createCurrencyFilterConfig,
    createDateFilterFields,
    createPaymentMethodFilterConfig,
    createSelectFilterFields,
    createStatusFilterConfig,
    createTextFilterField,
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
            createStatusFilterConfig(apPaymentStatusOptions),
            createPaymentMethodFilterConfig(apPaymentMethodOptions),
            createCurrencyFilterConfig(),
        ]),
        ...createDateFilterFields([
            createColumnDateFilterConfig(
                'payment_date_from',
                'Payment Date From',
            ),
            createColumnDateFilterConfig('payment_date_to', 'Payment Date To'),
        ]),
    ];
}
