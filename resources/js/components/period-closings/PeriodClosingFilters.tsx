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
    { value: 'yearly', label: 'Yearly' },
];

export function createPeriodClosingFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search...'),
        createSelectFilterField(
            'status',
            'Status',
            statusOptions,
            'Select Status',
        ),
        createSelectFilterField(
            'closing_type',
            'Closing Type',
            closingTypeOptions,
            'Select Type',
        ),
        {
            name: 'fiscal_year_id',
            label: 'Fiscal Year',
            component: (
                <AsyncSelect
                    url="/api/fiscal-years"
                    placeholder="Select Fiscal Year"
                />
            ),
        },
    ];
}
