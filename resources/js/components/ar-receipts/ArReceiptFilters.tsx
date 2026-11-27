'use client';

import {
    createColumnDateFilterConfig,
    createCurrencyFilterConfig,
    createCustomerFinancialFilterFields,
    createDateFilterFields,
    createPaymentMethodFilterConfig,
    createSelectFilterFields,
    createStatusFilterConfig,
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
            createStatusFilterConfig(arReceiptStatusOptions),
            createPaymentMethodFilterConfig(arReceiptPaymentMethodOptions),
            createCurrencyFilterConfig(),
        ]),
        ...createDateFilterFields([
            createColumnDateFilterConfig(
                'receipt_date_from',
                'Receipt Date From',
            ),
            createColumnDateFilterConfig('receipt_date_to', 'Receipt Date To'),
        ]),
    ];
}
