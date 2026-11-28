'use client';

import {
    createAsyncSelectFilterFields,
    createSelectFilterField,
    createTextFilterField,
    stockTransferStatusOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockTransferFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.stock_transfers.search_placeholder',
                fallback: 'Search stock transfers...',
            },
        ),
        ...createAsyncSelectFilterFields([
            {
                name: 'from_warehouse_id',
                label: {
                    key: 'column.from_warehouse',
                    fallback: 'From Warehouse',
                },
                url: '/api/warehouses',
                placeholder: {
                    key: 'filter_field.select_warehouse',
                    fallback: 'Select warehouse',
                },
            },
            {
                name: 'to_warehouse_id',
                label: { key: 'column.to_warehouse', fallback: 'To Warehouse' },
                url: '/api/warehouses',
                placeholder: {
                    key: 'filter_field.select_warehouse',
                    fallback: 'Select warehouse',
                },
            },
        ]),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            stockTransferStatusOptions,
            { key: 'filter_field.select_status', fallback: 'Select status' },
        ),
    ];
}
