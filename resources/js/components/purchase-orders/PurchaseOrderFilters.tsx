'use client';

import {
    createColumnDateFilterConfig,
    createCurrencyFilterConfig,
    createDateFilterFields,
    createSelectFilterFields,
    createStatusFilterConfig,
    createSupplierWarehouseFilterFields,
    createTextFilterField,
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
            createStatusFilterConfig(purchaseOrderStatusOptions),
            createCurrencyFilterConfig(),
        ]),
        ...createDateFilterFields([
            createColumnDateFilterConfig('order_date_from', 'Order Date From'),
            createColumnDateFilterConfig('order_date_to', 'Order Date To'),
        ]),
    ];
}
