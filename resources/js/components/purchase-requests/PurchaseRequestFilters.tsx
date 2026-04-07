'use client';

import {
    createDateFilterFields,
    createPurchaseRequestContextFilterFields,
    createSelectFilterFields,
    createTextFilterField,
    purchaseRequestPriorityOptions,
    purchaseRequestStatusOptions,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createPurchaseRequestFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            'Search',
            'Search PR number, notes, or rejection reason...',
        ),
        ...createPurchaseRequestContextFilterFields(),
        ...createSelectFilterFields([
            {
                name: 'priority',
                label: 'Priority',
                options: purchaseRequestPriorityOptions,
                placeholder: 'Select Priority',
            },
            {
                name: 'status',
                label: 'Status',
                options: purchaseRequestStatusOptions,
                placeholder: 'Select Status',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'request_date_from',
                label: 'Request Date From',
                placeholder: 'Request Date From',
            },
            {
                name: 'request_date_to',
                label: 'Request Date To',
                placeholder: 'Request Date To',
            },
        ]),
    ];
}
