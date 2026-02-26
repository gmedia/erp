'use client';

import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createStockTransferFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search stock transfers...'),
        createAsyncSelectFilterField(
            'from_warehouse_id',
            'From Warehouse',
            '/api/warehouses',
            'Select warehouse',
        ),
        createAsyncSelectFilterField(
            'to_warehouse_id',
            'To Warehouse',
            '/api/warehouses',
            'Select warehouse',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { label: 'Draft', value: 'draft' },
                { label: 'Pending Approval', value: 'pending_approval' },
                { label: 'Approved', value: 'approved' },
                { label: 'In Transit', value: 'in_transit' },
                { label: 'Received', value: 'received' },
                { label: 'Cancelled', value: 'cancelled' },
            ],
            'Select status',
        ),
    ];
}
