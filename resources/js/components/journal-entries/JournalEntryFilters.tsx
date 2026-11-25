import {
    createDateRangeFilterFields,
    createJournalEntryStatusFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createJournalEntryFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'column.search', fallback: 'Search' },
            {
                key: 'page.journal_entries.search_placeholder',
                fallback: 'Search entry...',
            },
        ),
        createJournalEntryStatusFilterField(),
        ...createDateRangeFilterFields(),
    ];
}
