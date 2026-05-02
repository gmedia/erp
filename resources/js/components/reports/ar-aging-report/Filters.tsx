'use client';

import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const customerInvoiceStatusOptions = [
    { label: 'Draft', value: 'draft' },
    { label: 'Sent', value: 'sent' },
    { label: 'Partially Paid', value: 'partially_paid' },
    { label: 'Paid', value: 'paid' },
    { label: 'Overdue', value: 'overdue' },
    { label: 'Cancelled', value: 'cancelled' },
    { label: 'Void', value: 'void' },
];

export function createArAgingReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search invoice number, customer, or branch...',
        ),
        createAsyncSelectFilterField(
            'customer_id',
            'Customer',
            '/api/customers',
            'Select customer',
        ),
        createAsyncSelectFilterField(
            'branch_id',
            'Branch',
            '/api/branches',
            'Select branch',
        ),
        createSelectFilterField(
            'status',
            'Status',
            customerInvoiceStatusOptions,
            'Select status',
        ),
        ...createDateRangeFilterFields('Start Date', 'End Date'),
    ];
}