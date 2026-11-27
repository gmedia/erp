import { SettingsShell } from '@/components/common/settings';
import { type NavItem } from '@/types';
import { type PropsWithChildren } from 'react';

interface AdminSettingsLayoutProps extends PropsWithChildren {
    currentGroup?: string;
}

const sidebarNavItems: NavItem[] = [
    {
        title: 'General',
        href: '/admin-settings',
        icon: null,
    },
    {
        title: 'Regional',
        href: '/admin-settings?group=regional',
        icon: null,
    },
    {
        title: 'SMTP',
        href: '/admin-settings?group=smtp',
        icon: null,
    },
    {
        title: 'Authentication',
        href: '/admin-settings?group=authentication',
        icon: null,
    },
    {
        title: 'Accounting',
        href: '/admin-settings?group=accounting',
        icon: null,
    },
    {
        title: 'Security Logs',
        href: '/admin-settings?group=security-logs',
        icon: null,
    },
];

export default function AdminSettingsLayout({
    children,
    currentGroup = 'general',
}: Readonly<AdminSettingsLayoutProps>) {
    return (
        <SettingsShell
            title="Admin Settings"
            description="Manage application-wide settings and preferences"
            items={sidebarNavItems.map((item) => ({
                title: item.title,
                href: item.href,
                icon: item.icon,
                active:
                    (currentGroup === 'general' && !item.href.includes('?')) ||
                    item.href.includes(`group=${currentGroup}`),
            }))}
        >
            {children}
        </SettingsShell>
    );
}
