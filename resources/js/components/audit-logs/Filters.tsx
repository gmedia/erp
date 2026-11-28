import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createAuditLogFilterFields(
    t: (key: string) => string,
): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'common.search', fallback: 'Search' },
            {
                key: 'page.audit_logs.search_placeholder',
                fallback: 'Search summary, document ID...',
            },
        ),
        createTextFilterField(
            'subject_type',
            { key: 'page.audit_logs.column_module', fallback: 'Module' },
            'e.g. JournalEntry',
        ),
        createAsyncSelectFilterField(
            'causer_id',
            { key: 'page.audit_logs.column_user', fallback: 'User' },
            '/api/users',
            t('page.audit_logs.select_user'),
        ),
        {
            name: 'start_date',
            label: t('page.audit_logs.start_date'),
            component: (
                <FilterDatePicker
                    placeholder={t('page.audit_logs.start_date')}
                />
            ),
        },
        {
            name: 'end_date',
            label: t('page.audit_logs.end_date'),
            component: (
                <FilterDatePicker placeholder={t('page.audit_logs.end_date')} />
            ),
        },
    ];
}
