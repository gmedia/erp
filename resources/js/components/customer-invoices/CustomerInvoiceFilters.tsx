'use client';

import {
    createColumnDateFilterConfig,
    createCurrencyFilterConfig,
    createCustomerFinancialFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    createStatusFilterConfig,
    translatedOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createCustomerInvoiceFilterFields(): FieldDescriptor[] {
    const customerInvoiceStatusOptions = translatedOptions('status.', [
        ['draft', 'Draft'],
        ['sent', 'Sent'],
        ['partially_paid', 'Partially Paid'],
        ['paid', 'Paid'],
        ['overdue', 'Overdue'],
        ['cancelled', 'Cancelled'],
        ['void', 'Void'],
    ]);

    return [
        ...createCustomerFinancialFilterFields({
            key: 'page.customer_invoices.search_placeholder',
            fallback: 'Search invoice number, customer, or notes...',
        }),
        ...createSelectFilterFields([
            createStatusFilterConfig(customerInvoiceStatusOptions),
            createCurrencyFilterConfig(),
        ]),
        ...createDateFilterFields([
            createColumnDateFilterConfig(
                'invoice_date_from',
                'Invoice Date From',
            ),
            createColumnDateFilterConfig('invoice_date_to', 'Invoice Date To'),
            createColumnDateFilterConfig('due_date_from', 'Due Date From'),
            createColumnDateFilterConfig('due_date_to', 'Due Date To'),
        ]),
    ];
}
