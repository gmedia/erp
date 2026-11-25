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
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.purchase_orders.filter_search_placeholder',
                fallback:
                    'Search PO number, payment terms, notes, or shipping address...',
            },
        ),
        ...createSupplierWarehouseFilterFields(),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: purchaseOrderStatusOptions,
                placeholder: 'Select Status',
            },
            {
                name: 'currency',
                label: { key: 'column.currency', fallback: 'Currency' },
                options: currencyOptions,
                placeholder: 'Select Currency',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'order_date_from',
                label: {
                    key: 'column.order_date_from',
                    fallback: 'Order Date From',
                },
                placeholder: 'Order Date From',
            },
            {
                name: 'order_date_to',
                label: {
                    key: 'column.order_date_to',
                    fallback: 'Order Date To',
                },
                placeholder: 'Order Date To',
            },
        ]),
    ];
}
