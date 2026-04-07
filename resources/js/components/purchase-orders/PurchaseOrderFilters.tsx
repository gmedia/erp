'use client';

import {
    createDateFilterFields,
    createSelectFilterFields,
    createSupplierWarehouseFilterFields,
    createTextFilterField,
    currencyOptions,
    purchaseOrderStatusOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPurchaseOrderFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search PO number, payment terms, notes, or shipping address...',
        ),
        ...createSupplierWarehouseFilterFields(),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: 'Status',
                options: purchaseOrderStatusOptions,
                placeholder: 'Select Status',
            },
            {
                name: 'currency',
                label: 'Currency',
                options: currencyOptions,
                placeholder: 'Select Currency',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'order_date_from',
                label: 'Order Date From',
                placeholder: 'Order Date From',
            },
            {
                name: 'order_date_to',
                label: 'Order Date To',
                placeholder: 'Order Date To',
            },
        ]),
    ];
}
