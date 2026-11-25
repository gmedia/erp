'use client';

import {
    createSupplierFilterSelectFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

// Supplier-specific filter fields
export function createSupplierFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search suppliers...',
        ),
        ...createSupplierFilterSelectFields(),
    ];
}
