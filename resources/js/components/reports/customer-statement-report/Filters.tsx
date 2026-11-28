'use client';

import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createCustomerStatementReportFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.customer_statement.search_placeholder',
                fallback: 'Search invoice number...',
            },
        ),
        createAsyncSelectFilterField(
            'customer_id',
            { key: 'column.customer', fallback: 'Customer' },
            '/api/customers',
            { key: 'filter.select_customer', fallback: 'Select customer' },
        ),
        ...createDateRangeFilterFields(
            { key: 'column.start_date', fallback: 'Start Date' },
            { key: 'column.end_date', fallback: 'End Date' },
        ),
    ];
}
