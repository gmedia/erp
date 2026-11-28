'use client';

import {
    createDateFilterFields,
    createEmployeeLookupFilterField,
    createPurchaseOrderFilterField,
    createTextFilterField,
    createWarehouseStatusFilterFields,
    draftConfirmedCancelledStatusOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createGoodsReceiptFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'filter_field.search', fallback: 'Search' },
            {
                key: 'page.goods_receipts.search_placeholder',
                fallback:
                    'Search GR number, supplier delivery note, or notes...',
            },
        ),
        createPurchaseOrderFilterField(),
        ...createWarehouseStatusFilterFields(
            draftConfirmedCancelledStatusOptions,
            {
                key: 'filter_field.select_warehouse',
                fallback: 'Select a warehouse',
            },
            {
                key: 'filter.select_status_title',
                fallback: 'Select Status',
            },
        ),
        createEmployeeLookupFilterField(
            'received_by',
            { key: 'column.received_by', fallback: 'Received By' },
            {
                key: 'filter_field.select_employee',
                fallback: 'Select employee',
            },
        ),
        ...createDateFilterFields([
            {
                name: 'receipt_date_from',
                label: {
                    key: 'filter.receipt_date_from',
                    fallback: 'Receipt Date From',
                },
                placeholder: {
                    key: 'filter.receipt_date_from',
                    fallback: 'Receipt Date From',
                },
            },
            {
                name: 'receipt_date_to',
                label: {
                    key: 'filter.receipt_date_to',
                    fallback: 'Receipt Date To',
                },
                placeholder: {
                    key: 'filter.receipt_date_to',
                    fallback: 'Receipt Date To',
                },
            },
        ]),
    ];
}
