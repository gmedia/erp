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
        createTextFilterField('search', 'Search', 'Search stock transfers...'),
        ...createAsyncSelectFilterFields([
            {
                name: 'from_warehouse_id',
                label: {
                    key: 'column.from_warehouse',
                    fallback: 'From Warehouse',
                },
                url: '/api/warehouses',
                placeholder: 'Select warehouse',
            },
            {
                name: 'to_warehouse_id',
                label: { key: 'column.to_warehouse', fallback: 'To Warehouse' },
                url: '/api/warehouses',
                placeholder: 'Select warehouse',
            },
        ]),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            stockTransferStatusOptions,
            'Select status',
        ),
    ];
}
