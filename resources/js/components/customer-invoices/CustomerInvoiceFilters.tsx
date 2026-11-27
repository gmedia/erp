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
        { value: 'draft', label: { key: 'status.draft', fallback: 'Draft' } },
        { value: 'sent', label: { key: 'status.sent', fallback: 'Sent' } },
        {
            value: 'partially_paid',
            label: { key: 'status.partially_paid', fallback: 'Partially Paid' },
        },
        { value: 'paid', label: { key: 'status.paid', fallback: 'Paid' } },
        {
            value: 'overdue',
            label: { key: 'status.overdue', fallback: 'Overdue' },
        },
        {
            value: 'cancelled',
            label: { key: 'status.cancelled', fallback: 'Cancelled' },
        },
        { value: 'void', label: { key: 'status.void', fallback: 'Void' } },
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
                placeholder: {
                    key: 'filter.select_status',
                    fallback: 'Select Status',
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
                name: 'invoice_date_from',
                label: {
                    key: 'column.invoice_date_from',
                    fallback: 'Invoice Date From',
                },
                placeholder: {
                    key: 'column.invoice_date_from',
                    fallback: 'Invoice Date From',
                },
            },
            {
                name: 'invoice_date_to',
                label: {
                    key: 'column.invoice_date_to',
                    fallback: 'Invoice Date To',
                },
                placeholder: {
                    key: 'column.invoice_date_to',
                    fallback: 'Invoice Date To',
                },
            },
            {
                name: 'due_date_from',
                label: {
                    key: 'column.due_date_from',
                    fallback: 'Due Date From',
                },
                placeholder: {
                    key: 'column.due_date_from',
                    fallback: 'Due Date From',
                },
            },
            {
                name: 'due_date_to',
                label: { key: 'column.due_date_to', fallback: 'Due Date To' },
                placeholder: {
                    key: 'column.due_date_to',
                    fallback: 'Due Date To',
                },
            },
        ]),
    ];
}
