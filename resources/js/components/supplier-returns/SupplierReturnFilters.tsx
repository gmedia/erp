'use client';

import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createSupplierReturnFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search return number or notes...',
        ),
        createAsyncSelectFilterField(
            'purchase_order_id',
            'Purchase Order',
            '/api/purchase-orders',
            'Select purchase order',
        ),
        createAsyncSelectFilterField(
            'goods_receipt_id',
            'Goods Receipt',
            '/api/goods-receipts',
            'Select goods receipt',
        ),
        createAsyncSelectFilterField(
            'supplier_id',
            'Supplier',
            '/api/suppliers',
            'Select supplier',
        ),
        createAsyncSelectFilterField(
            'warehouse_id',
            'Warehouse',
            '/api/warehouses',
            'Select warehouse',
        ),
        createSelectFilterField(
            'reason',
            'Reason',
            [
                { value: 'defective', label: 'Defective' },
                { value: 'wrong_item', label: 'Wrong Item' },
                { value: 'excess_quantity', label: 'Excess Quantity' },
                { value: 'damaged', label: 'Damaged' },
                { value: 'other', label: 'Other' },
            ],
            'Select reason',
        ),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'confirmed', label: 'Confirmed' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            'Select status',
        ),
        {
            name: 'return_date_from',
            label: 'Return Date From',
            component: <FilterDatePicker placeholder="Return Date From" />,
        },
        {
            name: 'return_date_to',
            label: 'Return Date To',
            component: <FilterDatePicker placeholder="Return Date To" />,
        },
    ];
}
