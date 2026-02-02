import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
    type SelectOption,
} from '@/components/common/filters';

export function createJournalEntryFilterFields(): FieldDescriptor[] {
    const statusOptions: SelectOption[] = [
        { value: 'draft', label: 'Draft' },
        { value: 'posted', label: 'Posted' },
        { value: 'void', label: 'Void' },
    ];

    return [
        createTextFilterField('search', 'Search', 'Search entry...'),
        createSelectFilterField('status', 'Status', statusOptions, 'Select status'),
        {
            name: 'start_date',
            label: 'Start Date',
            component: <FilterDatePicker placeholder="Start Date" />,
        },
        {
            name: 'end_date',
            label: 'End Date',
            component: <FilterDatePicker placeholder="End Date" />,
        },
    ];
}
