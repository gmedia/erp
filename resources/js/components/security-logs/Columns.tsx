import {
    LabelText,
    type TranslatableLabel,
} from '@/components/common/TranslatedText';
import { Badge } from '@/components/ui/badge';
import { securityEventBadgeVariant } from '@/lib/status-badge';
import { createSortingHeader } from '@/utils/columns';
import { formatDateTimeByRegionalSettings } from '@/utils/date-format';
import type { ColumnDef } from '@tanstack/react-table';

export interface SecurityLogItem {
    id: number;
    event: string;
    user_id: number | null;
    user_name: string | null;
    attempted_email: string | null;
    ip_address: string | null;
    user_agent: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string;
}

const EVENT_LABELS: Record<string, string> = {
    login_success: 'Login Success',
    login_failed: 'Login Failed',
    logout: 'Logout',
    password_reset: 'Password Reset',
    role_changed: 'Role Changed',
    user_created: 'User Created',
    user_deactivated: 'User Deactivated',
};

function columnLabel(column: string, fallback: string): TranslatableLabel {
    return { key: `page.security_logs.column_${column}`, fallback };
}

export function summarizeUserAgent(userAgent: string | null): string {
    if (!userAgent) {
        return '-';
    }
    const browser = /(Edg|Chrome|Firefox|Safari)\//.exec(userAgent)?.[1];
    const os = /(Windows|Mac OS X|Android|iPhone|Linux)/.exec(userAgent)?.[1];

    return [browser, os].filter(Boolean).join(' · ') || userAgent.slice(0, 60);
}

export const createSecurityLogColumns = (): ColumnDef<SecurityLogItem>[] => [
    {
        accessorKey: 'created_at',
        ...createSortingHeader(columnLabel('date', 'Date')),
        cell: ({ row }) => (
            <div>
                {formatDateTimeByRegionalSettings(
                    row.getValue('created_at') as string,
                )}
            </div>
        ),
    },
    {
        accessorKey: 'event',
        ...createSortingHeader(columnLabel('event', 'Event')),
        cell: ({ row }) => {
            const event = row.getValue('event') as string;
            return (
                <Badge variant={securityEventBadgeVariant(event)}>
                    <LabelText
                        label={{
                            key: `filter.security_event_${event}`,
                            fallback: EVENT_LABELS[event] ?? event,
                        }}
                    />
                </Badge>
            );
        },
    },
    {
        accessorKey: 'user_name',
        header: () => <LabelText label={columnLabel('user', 'User')} />,
        cell: ({ row }) => <div>{row.original.user_name ?? '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'attempted_email',
        header: () => (
            <LabelText
                label={columnLabel('attempted_email', 'Attempted Email')}
            />
        ),
        cell: ({ row }) => <div>{row.original.attempted_email ?? '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'ip_address',
        header: () => (
            <LabelText label={columnLabel('ip_address', 'IP Address')} />
        ),
        cell: ({ row }) => <div>{row.original.ip_address ?? '-'}</div>,
        enableSorting: false,
    },
    {
        accessorKey: 'user_agent',
        header: () => <LabelText label={columnLabel('device', 'Device')} />,
        cell: ({ row }) => (
            <div title={row.original.user_agent ?? undefined}>
                {summarizeUserAgent(row.original.user_agent)}
            </div>
        ),
        enableSorting: false,
    },
];
