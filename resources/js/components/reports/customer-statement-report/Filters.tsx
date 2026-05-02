'use client';

import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createCustomerStatementReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search invoice number...'),
        createAsyncSelectFilterField(
            'customer_id',
            'Customer',
            '/api/customers',
            'Select customer',
        ),
        ...createDateRangeFilterFields('Start Date', 'End Date'),
    ];
}
