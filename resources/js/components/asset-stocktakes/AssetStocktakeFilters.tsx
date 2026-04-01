import {
    type FieldDescriptor,
    createAsyncSelectFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    createTextFilterField,
} from '@/components/common/filters';

export function createAssetStocktakeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search by reference...'),
        ...createAsyncSelectFilterFields([
            {
                name: 'branch_id',
                label: 'Branch',
                url: '/api/branches',
                placeholder: 'All Branches',
            },
        ]),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: 'Status',
                options: [
                    { value: 'draft', label: 'Draft' },
                    { value: 'in_progress', label: 'In Progress' },
                    { value: 'completed', label: 'Completed' },
                    { value: 'cancelled', label: 'Cancelled' },
                ],
                placeholder: 'All Statuses',
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'planned_at_from',
                label: 'Planned From',
                placeholder: 'Planned From',
            },
            {
                name: 'planned_at_to',
                label: 'Planned To',
                placeholder: 'Planned To',
            },
        ]),
    ];
}
