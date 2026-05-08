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
            'Search',
            'Search payment number, reference, or notes...',
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            'Supplier',
            '/api/suppliers',
            'All suppliers',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'All branches',
        ),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: 'Status',
                options: apPaymentStatusOptions,
                placeholder: 'Select Status',
            },
            {
                name: 'payment_method',
                label: 'Payment Method',
                options: apPaymentMethodOptions,
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
                name: 'payment_date_from',
                label: 'Payment Date From',
                placeholder: 'Payment Date From',
            },
            {
                name: 'payment_date_to',
                label: 'Payment Date To',
                placeholder: 'Payment Date To',
            },
        ]),
    ];
}
