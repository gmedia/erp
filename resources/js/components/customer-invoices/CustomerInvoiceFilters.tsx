'use client';

import {
    createCustomerFinancialFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
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
        ...createCustomerFinancialFilterFields({
            key: 'page.customer_invoices.search_placeholder',
            fallback: 'Search invoice number, customer, or notes...',
        }),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: customerInvoiceStatusOptions,
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
                name: 'invoice_date_from',
                label: {
                    key: 'column.invoice_date_from',
                    fallback: 'Invoice Date From',
                },
                placeholder: 'Invoice Date From',
            },
            {
                name: 'invoice_date_to',
                label: {
                    key: 'column.invoice_date_to',
                    fallback: 'Invoice Date To',
                },
                placeholder: 'Invoice Date To',
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
