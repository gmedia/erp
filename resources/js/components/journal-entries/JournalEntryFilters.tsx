import { DatePickerField } from '@/components/common/DatePickerField';
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
            component: <DatePickerField name="start_date" label="Start Date" placeholder="Start Date" />,
        },
        {
            name: 'end_date',
            label: 'End Date',
            component: <DatePickerField name="end_date" label="End Date" placeholder="End Date" />,
        },
    ];
}
