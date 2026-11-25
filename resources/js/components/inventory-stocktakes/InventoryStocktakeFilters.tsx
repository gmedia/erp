'use client';

import {
    createInventoryStocktakeFilterSelectFields,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createInventoryStocktakeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', {
            key: 'page.inventory_stocktakes.search_placeholder',
            fallback: 'Search inventory stocktakes...',
        }),
        ...createInventoryStocktakeFilterSelectFields(),
    ];
}
