'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
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
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'Select warehouse',
        ),
        createAsyncSelectFilterField(
            'product_category_id',
            'Product Category',
            '/api/product-categories',
            'Select category',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { label: 'Draft', value: 'draft' },
                { label: 'In Progress', value: 'in_progress' },
                { label: 'Completed', value: 'completed' },
                { label: 'Cancelled', value: 'cancelled' },
            ],
            'Select status',
        ),
    ];
}
