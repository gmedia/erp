import { type FieldDescriptor } from '@/components/common/filters';

export const createApprovalFlowFilterFields = (): FieldDescriptor[] => [
    {
        name: 'search',
        label: 'Search',
        type: 'text',
        placeholder: 'Search by Code or Name...',
    },
    {
        name: 'approvable_type',
        label: 'Approvable Type',
        type: 'select',
        options: [
            { label: 'All Types', value: '' },
            { label: 'Purchase Request', value: 'App\\Models\\PurchaseRequest' },
            { label: 'Asset Movement', value: 'App\\Models\\AssetMovement' },
            { label: 'Asset Maintenance', value: 'App\\Models\\AssetMaintenance' },
        ],
    },
    {
        name: 'is_active',
        label: 'Status',
        type: 'select',
        options: [
            { label: 'All Statuses', value: '' },
            { label: 'Active', value: '1' },
            { label: 'Inactive', value: '0' },
        ],
    },
];
