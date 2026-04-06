'use client';

import {
    createInventoryStocktakeFilterSelectFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createInventoryStocktakeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search inventory stocktakes...',
        ),
        ...createInventoryStocktakeFilterSelectFields(),
    ];
}
