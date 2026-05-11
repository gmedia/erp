import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const frequencyOptions = [
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' },
    { value: 'quarterly', label: 'Quarterly' },
    { value: 'yearly', label: 'Yearly' },
];

const activeStatusOptions = [
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

export function createRecurringJournalFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search name, description...'),
        createSelectFilterField('frequency', 'Frequency', frequencyOptions, 'Select Frequency'),
        createSelectFilterField('is_active', 'Status', activeStatusOptions, 'Select Status'),
    ];
}
