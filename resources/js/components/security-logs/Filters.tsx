import { FilterDatePicker } from '@/components/common/FilterDatePicker';
import {
    createAsyncSelectFilterField,
    createSecurityLogEventFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createSecurityLogFilterFields(
    t: (key: string) => string,
): FieldDescriptor[] {
    return [
        createTextFilterField(
            'search',
            { key: 'common.search', fallback: 'Search' },
            {
                key: 'page.security_logs.search_placeholder',
                fallback: 'Search email, IP, user...',
            },
        ),
        createSecurityLogEventFilterField(),
        createAsyncSelectFilterField(
            'user_id',
            { key: 'page.security_logs.column_user', fallback: 'User' },
            '/api/users',
            t('page.security_logs.select_user'),
        ),
        createTextFilterField(
            'ip_address',
            {
                key: 'page.security_logs.column_ip_address',
                fallback: 'IP Address',
            },
            {
                key: 'page.security_logs.ip_placeholder',
                fallback: 'e.g. 192.168.1.10',
            },
        ),
        {
            name: 'start_date',
            label: t('page.security_logs.start_date'),
            component: (
                <FilterDatePicker
                    placeholder={t('page.security_logs.pick_date')}
                />
            ),
        },
        {
            name: 'end_date',
            label: t('page.security_logs.end_date'),
            component: (
                <FilterDatePicker
                    placeholder={t('page.security_logs.pick_date')}
                />
            ),
        },
    ];
}
