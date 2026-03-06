'use client';

import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createGoodsReceiptFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search GR number, supplier delivery note, or notes...',
        ),
        createAsyncSelectFilterField(
            'purchase_order_id',
            'Purchase Order',
            '/api/purchase-orders',
            'Select purchase order',
        ),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'Select a warehouse',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            'Select Status',
        ),
        createAsyncSelectFilterField(
            'received_by',
            'Received By',
            '/api/employees',
            'Select employee',
        ),
        {
            name: 'receipt_date_from',
            label: 'Receipt Date From',
            component: <FilterDatePicker placeholder="Receipt Date From" />,
        },
        {
            name: 'receipt_date_to',
            label: 'Receipt Date To',
            component: <FilterDatePicker placeholder="Receipt Date To" />,
        },
    ];
}
