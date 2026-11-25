import { AsyncSelect } from '@/components/common/AsyncSelect';
import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const statusOptions = [
    { value: 'draft', label: 'Draft' },
    { value: 'closed', label: 'Closed' },
];

const closingTypeOptions = [
    { value: 'monthly', label: 'Monthly' },
    { value: 'annual', label: 'Annual' },
];

export function createPeriodClosingFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.period_closings.search_placeholder',
                fallback: 'Search...',
            },
        ),
        createSelectFilterField(
            'status',
            { key: 'column.status', fallback: 'Status' },
            statusOptions,
            'Select Status',
        ),
        createSelectFilterField(
            'closing_type',
            { key: 'column.closing_type', fallback: 'Closing Type' },
            closingTypeOptions,
            'Select Type',
        ),
        {
            name: 'fiscal_year_id',
            label: { key: 'column.fiscal_year', fallback: 'Fiscal Year' },
            component: (
                <AsyncSelect
                    url="/api/fiscal-years"
                    placeholder="Select Fiscal Year"
                />
            ),
        },
    ];
}
