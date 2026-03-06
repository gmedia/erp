'use client';

import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPurchaseOrderFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search PO number, payment terms, notes, or shipping address...',
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            'Supplier',
            '/api/suppliers',
            'Select a supplier',
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
                { value: 'pending_approval', label: 'Pending Approval' },
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'rejected', label: 'Rejected' },
                { value: 'partially_received', label: 'Partially Received' },
                { value: 'fully_received', label: 'Fully Received' },
                { value: 'cancelled', label: 'Cancelled' },
                { value: 'closed', label: 'Closed' },
            ],
            'Select Status',
        ),
        createSelectFilterField(
            'currency',
            'Currency',
            [
                { value: 'IDR', label: 'IDR' },
                { value: 'USD', label: 'USD' },
                { value: 'EUR', label: 'EUR' },
            ],
            'Select Currency',
        ),
        {
            name: 'order_date_from',
            label: 'Order Date From',
            component: <FilterDatePicker placeholder="Order Date From" />,
        },
        {
            name: 'order_date_to',
            label: 'Order Date To',
            component: <FilterDatePicker placeholder="Order Date To" />,
        },
    ];
}
