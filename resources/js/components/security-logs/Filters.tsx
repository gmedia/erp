import {
    createDateRangeFilterFields,
    createSecurityLogEventFilterField,
    createTextFilterField,
    createUserFilterField,
    type FieldDescriptor,
} from '@/components/common/filters';

export function createSecurityLogFilterFields(): FieldDescriptor[] {
    return [
        createTextFilterField('search', 'Search', {
            key: 'page.security_logs.search_placeholder',
            fallback: 'Search email, IP, user...',
        }),
        createSecurityLogEventFilterField(),
        createUserFilterField('user_id', 'User'),
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
