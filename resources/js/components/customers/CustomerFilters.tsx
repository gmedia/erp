'use client';

import {
    createCustomerFilterSelectFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

// Customer-specific filter fields
export function createCustomerFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.customers.search_placeholder',
                fallback: 'Search customers...',
            },
        ),
        ...createCustomerFilterSelectFields(),
    ];
}
