'use client';

import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const typeOptions = [
    { label: 'Purchase Request', value: 'App\\Models\\PurchaseRequest' },
    { label: 'Asset Movement', value: 'App\\Models\\AssetMovement' },
    { label: 'Asset Maintenance', value: 'App\\Models\\AssetMaintenance' },
];

const statusOptions = [
    { label: 'Active', value: '1' },
    { label: 'Inactive', value: '0' },
];

export function createApprovalFlowFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search by Code or Name...'),
        createSelectFilterField('approvable_type', 'Approvable Type', typeOptions, 'All Types'),
        createSelectFilterField('is_active', 'Status', statusOptions, 'All Statuses'),
    ];
}
