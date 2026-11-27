'use client';

import {
    createCustomerFinancialFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    currencyOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

const arReceiptStatusOptions = [
    { value: 'draft', label: { key: 'status.draft', fallback: 'Draft' } },
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

const arReceiptPaymentMethodOptions = [
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
        value: 'credit_card',
        label: {
            key: 'filter.payment_method_credit_card',
            fallback: 'Credit Card',
        },
    },
    {
        value: 'other',
        label: { key: 'filter.payment_method_other', fallback: 'Other' },
    },
];

export function createArReceiptFilterFields(): FieldDescriptor[] {
    return [
        ...createCustomerFinancialFilterFields({
            key: 'page.ar_receipts.search_placeholder',
            fallback: 'Search receipt number, customer, or reference...',
        }),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: arReceiptStatusOptions,
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
                options: arReceiptPaymentMethodOptions,
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
                name: 'receipt_date_from',
                label: {
                    key: 'column.receipt_date_from',
                    fallback: 'Receipt Date From',
                },
                placeholder: {
                    key: 'column.receipt_date_from',
                    fallback: 'Receipt Date From',
                },
            },
            {
                name: 'receipt_date_to',
                label: {
                    key: 'column.receipt_date_to',
                    fallback: 'Receipt Date To',
                },
                placeholder: {
                    key: 'column.receipt_date_to',
                    fallback: 'Receipt Date To',
                },
            },
        ]),
    ];
}
