'use client';

import {
    createCustomerFinancialFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    currencyOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createArReceiptFilterFields(): FieldDescriptor[] {
    const arReceiptStatusOptions = [
        { value: 'draft', label: 'Draft' },
        { value: 'confirmed', label: 'Confirmed' },
        { value: 'reconciled', label: 'Reconciled' },
        { value: 'cancelled', label: 'Cancelled' },
        { value: 'void', label: 'Void' },
    ];

    const arReceiptPaymentMethodOptions = [
        { value: 'bank_transfer', label: 'Bank Transfer' },
        { value: 'cash', label: 'Cash' },
        { value: 'check', label: 'Check' },
        { value: 'giro', label: 'Giro' },
        { value: 'credit_card', label: 'Credit Card' },
        { value: 'other', label: 'Other' },
    ];

    return [
        ...createCustomerFinancialFilterFields(
            'Search receipt number, customer, or reference...',
        ),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: arReceiptStatusOptions,
                placeholder: 'Select Status',
            },
            {
                name: 'payment_method',
                label: {
                    key: 'column.payment_method',
                    fallback: 'Payment Method',
                },
                options: arReceiptPaymentMethodOptions,
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
                name: 'receipt_date_from',
                label: {
                    key: 'column.receipt_date_from',
                    fallback: 'Receipt Date From',
                },
                placeholder: 'Receipt Date From',
            },
            {
                name: 'receipt_date_to',
                label: {
                    key: 'column.receipt_date_to',
                    fallback: 'Receipt Date To',
                },
                placeholder: 'Receipt Date To',
            },
        ]),
    ];
}
