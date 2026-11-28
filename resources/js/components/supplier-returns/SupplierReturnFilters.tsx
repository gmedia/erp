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
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.supplier_returns.search_placeholder',
                fallback: 'Search return number or notes...',
            },
        ),
        createPurchaseOrderFilterField(),
        ...createAsyncSelectFilterFields([
            {
                name: 'goods_receipt_id',
                label: {
                    key: 'column.goods_receipt',
                    fallback: 'Goods Receipt',
                },
                url: '/api/goods-receipts',
                placeholder: {
                    key: 'filter.supplier_returns_select_goods_receipt',
                    fallback: 'Select goods receipt',
                },
            },
            {
                name: 'supplier_id',
                label: { key: 'column.supplier', fallback: 'Supplier' },
                url: '/api/suppliers',
                placeholder: {
                    key: 'filter.supplier_returns_select_supplier',
                    fallback: 'Select supplier',
                },
            },
            {
                name: 'warehouse_id',
                label: { key: 'column.warehouse', fallback: 'Warehouse' },
                url: '/api/warehouses',
                placeholder: {
                    key: 'filter.supplier_returns_select_warehouse',
                    fallback: 'Select warehouse',
                },
            },
        ]),
        ...createSelectFilterFields([
            {
                name: 'reason',
                label: { key: 'column.reason', fallback: 'Reason' },
                options: supplierReturnReasonOptions,
                placeholder: {
                    key: 'filter.supplier_returns_select_reason',
                    fallback: 'Select reason',
                },
            },
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: draftConfirmedCancelledStatusOptions,
                placeholder: {
                    key: 'filter.supplier_returns_select_status',
                    fallback: 'Select status',
                },
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'return_date_from',
                label: {
                    key: 'column.return_date_from',
                    fallback: 'Return Date From',
                },
                placeholder: {
                    key: 'column.return_date_from',
                    fallback: 'Return Date From',
                },
            },
            {
                name: 'return_date_to',
                label: {
                    key: 'column.return_date_to',
                    fallback: 'Return Date To',
                },
                placeholder: {
                    key: 'column.return_date_to',
                    fallback: 'Return Date To',
                },
            },
        ]),
    ];
}
