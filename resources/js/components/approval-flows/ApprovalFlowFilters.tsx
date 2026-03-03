'use client';

import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const typeOptions = [
    { label: 'Purchase Request', value: 'App\\Models\\PurchaseRequest' },
    { label: 'Purchase Order', value: 'App\\Models\\PurchaseOrder' },
    { label: 'Journal Entry', value: 'App\\Models\\JournalEntry' },
    { label: 'Asset', value: 'App\\Models\\Asset' },
    { label: 'Asset Movement', value: 'App\\Models\\AssetMovement' },
    { label: 'Asset Maintenance', value: 'App\\Models\\AssetMaintenance' },
    { label: 'Asset Stocktake', value: 'App\\Models\\AssetStocktake' },
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
