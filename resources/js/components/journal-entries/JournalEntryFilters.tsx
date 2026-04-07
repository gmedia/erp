import {
    createDateRangeFilterFields,
    createJournalEntryStatusFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createJournalEntryFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', 'Search entry...'),
        createJournalEntryStatusFilterField(),
        ...createDateRangeFilterFields(),
    ];
}
