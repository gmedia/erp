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
            { key: 'filter_field.search', fallback: 'Search' },
            {
                key: 'page.inventory_stocktakes.search_placeholder',
                fallback: 'Search inventory stocktakes...',
            },
        ),
        ...createInventoryStocktakeFilterSelectFields(),
    ];
}
