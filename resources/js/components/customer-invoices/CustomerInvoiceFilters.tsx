'use client';

import {
    createAsyncSelectFilterField,
    createDateFilterFields,
    createSelectFilterFields,
    createTextFilterField,
    currencyOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createCustomerInvoiceFilterFields(): FieldDescriptor[] {
    const customerInvoiceStatusOptions = [
        { value: 'draft', label: 'Draft' },
        { value: 'sent', label: 'Sent' },
        { value: 'partially_paid', label: 'Partially Paid' },
        { value: 'paid', label: 'Paid' },
        { value: 'overdue', label: 'Overdue' },
        { value: 'cancelled', label: 'Cancelled' },
        { value: 'void', label: 'Void' },
    ];

    return [
        createTextFilterField(
            'search',
            'Search',
            'Search invoice number, customer, or notes...',
        ),
        createAsyncSelectFilterField('customer_id', 'Customer', '/api/customers', 'Select Customer'),
        createAsyncSelectFilterField('branch_id', 'Branch', '/api/branches', 'Select Branch'),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: 'Status',
                options: customerInvoiceStatusOptions,
                placeholder: 'Select Status',
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
                name: 'invoice_date_from',
                label: 'Invoice Date From',
                placeholder: 'Invoice Date From',
            },
            {
                name: 'invoice_date_to',
                label: 'Invoice Date To',
                placeholder: 'Invoice Date To',
            },
            {
                name: 'due_date_from',
                label: 'Due Date From',
                placeholder: 'Due Date From',
            },
            {
                name: 'due_date_to',
                label: 'Due Date To',
                placeholder: 'Due Date To',
            },
        ]),
    ];
}