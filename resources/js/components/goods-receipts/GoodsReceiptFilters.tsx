'use client';

import { FilterDatePicker } from '@/components/common/FilterDatePicker';
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
            'Search',
            'Search GR number, supplier delivery note, or notes...',
        ),
        createPurchaseOrderFilterField(),
        ...createWarehouseStatusFilterFields(
            draftConfirmedCancelledStatusOptions,
            'Select a warehouse',
            'Select Status',
        ),
        createEmployeeLookupFilterField(
            'received_by',
            'Received By',
            'Select employee',
        ),
        ...createDateFilterFields([
            {
                name: 'receipt_date_from',
                label: 'Receipt Date From',
                placeholder: 'Receipt Date From',
            },
            {
                name: 'receipt_date_to',
                label: 'Receipt Date To',
                placeholder: 'Receipt Date To',
            },
        ]),
    ];
}
