'use client';

import {
    createCustomerFilterSelectFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

// Customer-specific filter fields
export function createCustomerFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search customers...'),
        ...createCustomerFilterSelectFields(),
    ];
}
