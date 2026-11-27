import {
    createAsyncSelectFilterField,
    createDateRangeFilterFields,
    createSecurityLogEventFilterField,
    createTextFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createSecurityLogFilterFields(): FieldDescriptor[] {
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
            'Select a user',
        ),
        createTextFilterField(
            'ip_address',
            {
                key: 'page.security_logs.column_ip_address',
                fallback: 'IP Address',
            },
            'e.g. 192.168.1.10',
        ),
        ...createDateRangeFilterFields(),
    ];
}
