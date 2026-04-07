'use client';

import {
    createAsyncSelectFilterFields,
    createTextFilterField,
    createSelectFilterField,
    stockTransferStatusOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockTransferFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search stock transfers...'),
        ...createAsyncSelectFilterFields([
            {
                name: 'from_warehouse_id',
                label: 'From Warehouse',
                url: '/api/warehouses',
                placeholder: 'Select warehouse',
            },
            {
                name: 'to_warehouse_id',
                label: 'To Warehouse',
                url: '/api/warehouses',
                placeholder: 'Select warehouse',
            },
        ]),
        createSelectFilterField(
            'status',
            'Status',
            stockTransferStatusOptions,
            'Select status',
        ),
    ];
}
