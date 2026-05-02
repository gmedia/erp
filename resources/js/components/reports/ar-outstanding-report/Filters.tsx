'use client';

import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const arOutstandingStatusOptions = [
    { label: 'Sent', value: 'sent' },
    { label: 'Partially Paid', value: 'partially_paid' },
    { label: 'Overdue', value: 'overdue' },
];

export function createArOutstandingReportFilterFields(): FieldDescriptor[] {
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
            arOutstandingStatusOptions,
            'Select status',
        ),
        ...createDateRangeFilterFields('Start Date', 'End Date'),
    ];
}