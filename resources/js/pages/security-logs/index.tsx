'use client';

import { ReportDataTablePage } from '@/components/common/ReportDataTablePage';
import {
    createSecurityLogColumns,
    type SecurityLogItem,
} from '@/components/security-logs/Columns';
import { createSecurityLogFilterFields } from '@/components/security-logs/Filters';

export default function SecurityLogs() {
    return (
        <ReportDataTablePage<SecurityLogItem>
            title="Security Logs"
            titleKey="menu.security_log"
            descriptionKey="page.security_logs.description"
            breadcrumbs={[
                { title: 'Admin', href: '#' },
                {
                    title: 'Security Logs',
                    titleKey: 'menu.security_log',
                    href: '/security-logs',
                },
            ]}
            columns={createSecurityLogColumns()}
            filterFields={createSecurityLogFilterFields()}
            initialFilters={{
                search: '',
                event: '',
                user_id: '',
                ip_address: '',
                start_date: '',
                end_date: '',
            }}
            endpoint="/api/security-logs"
            queryKey={['security-logs']}
            entityName="Security Log"
            exportEndpoint="/api/security-logs/export"
        />
    );
}
