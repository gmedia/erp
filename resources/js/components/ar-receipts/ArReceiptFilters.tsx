'use client';

import { createCustomerBranchFilterFields } from '@/components/common/customer-branch-filters';
import {
    createDateFilterFields,
    createSelectFilterFields,
    createTextFilterField,
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
        createTextFilterField(
            'search',
            'Search',
            'Search receipt number, customer, or reference...',
        ),
        ...createCustomerBranchFilterFields(),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: 'Status',
                options: arReceiptStatusOptions,
                placeholder: 'Select Status',
            },
            {
                name: 'payment_method',
                label: 'Payment Method',
                options: arReceiptPaymentMethodOptions,
                placeholder: 'Select Payment Method',
            },
            {
                name: 'currency',
                label: 'Currency',
                options: currencyOptions,
                placeholder: 'Select Currency',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'receipt_date_from',
                label: 'Receipt Date From',
                placeholder: 'Receipt Date From',
            },
            {
                name: 'receipt_date_to',
                label: 'Receipt Date To',
                placeholder: 'Receipt Date To',
            },
        ]),
    ];
}
