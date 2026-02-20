import { type FieldDescriptor } from '@/components/common/filters';

export function createAssetStocktakeFilterFields(): FieldDescriptor[] {
    return [
        {
            name: 'search',
            label: 'Search',
            type: 'text',
            placeholder: 'Search by reference...',
        },
        {
            name: 'branch_id',
            label: 'Branch',
            type: 'select-async',
            placeholder: 'All Branches',
            url: '/api/branches',
        },
        {
            name: 'status',
            label: 'Status',
            type: 'select',
            placeholder: 'All Statuses',
            options: [
                { value: 'draft', label: 'Draft' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
        },
        {
            name: 'planned_at_from',
            label: 'Planned From',
            type: 'date',
        },
        {
            name: 'planned_at_to',
            label: 'Planned To',
            type: 'date',
        },
    ];
}
