import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    type FieldDescriptor,
    createAsyncSelectFilterField,
    createSelectFilterField,
    createTextFilterField,
} from '@/components/common/filters';

export function createAssetStocktakeFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search by reference...'),
        createAsyncSelectFilterField('branch_id', 'Branch', '/api/branches', 'All Branches'),
        createSelectFilterField(
            'status',
            'Status',
            [
                { value: 'draft', label: 'Draft' },
                { value: 'in_progress', label: 'In Progress' },
                { value: 'completed', label: 'Completed' },
                { value: 'cancelled', label: 'Cancelled' },
            ],
            'All Statuses',
        ),
        {
            name: 'planned_at_from',
            label: 'Planned From',
            component: <FilterDatePicker placeholder="Planned From" />,
        },
        {
            name: 'planned_at_to',
            label: 'Planned To',
            component: <FilterDatePicker placeholder="Planned To" />,
        },
    ];
}
