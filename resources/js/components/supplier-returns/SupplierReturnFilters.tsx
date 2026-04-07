'use client';

import {
    createAsyncSelectFilterFields,
    createDateFilterFields,
    createPurchaseOrderFilterField,
    createSelectFilterFields,
    createTextFilterField,
    draftConfirmedCancelledStatusOptions,
    supplierReturnReasonOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createSupplierReturnFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search return number or notes...',
        ),
        createPurchaseOrderFilterField(),
        ...createAsyncSelectFilterFields([
            {
                name: 'goods_receipt_id',
                label: 'Goods Receipt',
                url: '/api/goods-receipts',
                placeholder: 'Select goods receipt',
            },
            {
                name: 'supplier_id',
                label: 'Supplier',
                url: '/api/suppliers',
                placeholder: 'Select supplier',
            },
            {
                name: 'warehouse_id',
                label: 'Warehouse',
                url: '/api/warehouses',
                placeholder: 'Select warehouse',
            },
        ]),
        ...createSelectFilterFields([
            {
                name: 'reason',
                label: 'Reason',
                options: supplierReturnReasonOptions,
                placeholder: 'Select reason',
            },
            {
                name: 'status',
                label: 'Status',
                options: draftConfirmedCancelledStatusOptions,
                placeholder: 'Select status',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'return_date_from',
                label: 'Return Date From',
                placeholder: 'Return Date From',
            },
            {
                name: 'return_date_to',
                label: 'Return Date To',
                placeholder: 'Return Date To',
            },
        ]),
    ];
}
