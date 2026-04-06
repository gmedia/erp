'use client';

import {
    createProductCatalogAsyncFilterFields,
    createProductTypeStatusFilterFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

/**
 * Creates filter fields for the product management module.
 */
export function createProductFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search code, name...'),
        ...createProductCatalogAsyncFilterFields(),
        ...createProductTypeStatusFilterFields(),
    ];
}
