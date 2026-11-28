import {
    createSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

const frequencyOptions = [
    {
        value: 'daily',
        label: { key: 'filter.recurring_frequency_daily', fallback: 'Daily' },
    },
    {
        value: 'weekly',
        label: { key: 'filter.recurring_frequency_weekly', fallback: 'Weekly' },
    },
    {
        value: 'monthly',
        label: {
            key: 'filter.recurring_frequency_monthly',
            fallback: 'Monthly',
        },
    },
    {
        value: 'quarterly',
        label: {
            key: 'filter.recurring_frequency_quarterly',
            fallback: 'Quarterly',
        },
    },
    {
        value: 'annual',
        label: { key: 'filter.recurring_frequency_annual', fallback: 'Yearly' },
    },
];

const activeStatusOptions = [
    { value: '1', label: { key: 'status.active', fallback: 'Active' } },
    { value: '0', label: { key: 'status.inactive', fallback: 'Inactive' } },
];

export function createRecurringJournalFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.recurring_journals.search_placeholder',
                fallback: 'Search name, description...',
            },
        ),
        createSelectFilterField(
            'frequency',
            { key: 'column.frequency', fallback: 'Frequency' },
            frequencyOptions,
            {
                key: 'filter.recurring_select_frequency',
                fallback: 'Select Frequency',
            },
        ),
        createSelectFilterField(
            'is_active',
            { key: 'column.status', fallback: 'Status' },
            activeStatusOptions,
            { key: 'filter.select_status', fallback: 'Select Status' },
        ),
    ];
}
