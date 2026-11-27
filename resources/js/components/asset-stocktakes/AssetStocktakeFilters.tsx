import {
    type FieldDescriptor,
    createAsyncSelectFilterFields,
    createDateFilterFields,
    createSelectFilterFields,
    createTextFilterField,
} from '@/components/common/filters';

export function createAssetStocktakeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.asset_stocktakes.search_placeholder',
                fallback: 'Search by reference...',
            },
        ),
        ...createAsyncSelectFilterFields([
            {
                name: 'branch_id',
                label: { key: 'column.branch', fallback: 'Branch' },
                url: '/api/branches',
                placeholder: {
                    key: 'filter.all_branches',
                    fallback: 'All Branches',
                },
            },
        ]),
        ...createSelectFilterFields([
            {
                name: 'status',
                label: { key: 'column.status', fallback: 'Status' },
                options: [
                    {
                        value: 'draft',
                        label: { key: 'status.draft', fallback: 'Draft' },
                    },
                    {
                        value: 'in_progress',
                        label: {
                            key: 'status.in_progress',
                            fallback: 'In Progress',
                        },
                    },
                    {
                        value: 'completed',
                        label: {
                            key: 'status.completed',
                            fallback: 'Completed',
                        },
                    },
                    {
                        value: 'cancelled',
                        label: {
                            key: 'status.cancelled',
                            fallback: 'Cancelled',
                        },
                    },
                ],
                placeholder: {
                    key: 'filter.all_statuses_title',
                    fallback: 'All Statuses',
                },
            },
        ]),
        ...createDateFilterFields([
            {
                name: 'planned_at_from',
                label: {
                    key: 'column.planned_from',
                    fallback: 'Planned From',
                },
                placeholder: {
                    key: 'column.planned_from',
                    fallback: 'Planned From',
                },
            },
            {
                name: 'planned_at_to',
                label: { key: 'column.planned_to', fallback: 'Planned To' },
                placeholder: {
                    key: 'column.planned_to',
                    fallback: 'Planned To',
                },
            },
        ]),
    ];
}
