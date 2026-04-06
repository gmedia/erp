'use client';

import {
    createSupplierFilterSelectFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

// Supplier-specific filter fields
export function createSupplierFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search suppliers...'),
        ...createSupplierFilterSelectFields(),
    ];
}
