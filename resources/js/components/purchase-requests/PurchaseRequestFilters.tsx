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
        createTextFilterField('search', 'Search', {
            key: 'page.purchase_requests.search_placeholder',
            fallback: 'Search PR number, notes, or rejection reason...',
        }),
        ...createPurchaseRequestContextFilterFields(),
        ...createSelectFilterFields([
            {
                name: 'priority',
                label: { key: 'column.priority', fallback: 'Priority' },
                options: purchaseRequestPriorityOptions,
                placeholder: 'Select Priority',
            },
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: purchaseRequestStatusOptions,
                placeholder: 'Select Status',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'request_date_from',
                label: {
                    key: 'column.request_date_from',
                    fallback: 'Request Date From',
                },
                placeholder: 'Request Date From',
            },
            {
                name: 'request_date_to',
                label: {
                    key: 'column.request_date_to',
                    fallback: 'Request Date To',
                },
                placeholder: 'Request Date To',
            },
        ]),
    ];
}
