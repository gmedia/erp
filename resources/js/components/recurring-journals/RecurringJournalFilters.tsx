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
    { value: 'annual', label: 'Yearly' },
];

const activeStatusOptions = [
    { value: '1', label: 'Active' },
    { value: '0', label: 'Inactive' },
];

export function createRecurringJournalFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            'Search name, description...',
        ),
        createSelectFilterField(
            'frequency',
            { key: 'column.frequency', fallback: 'Frequency' },
            frequencyOptions,
            'Select Frequency',
        ),
        createSelectFilterField(
            'is_active',
            { key: 'column.status', fallback: 'Status' },
            activeStatusOptions,
            'Select Status',
        ),
    ];
}
