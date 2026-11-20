import { SettingsShell } from '@/components/common/settings';
import { type NavItem } from '@/types';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
        icon: null,
    },
    {
        title: 'Password',
        href: '/settings/password',
        icon: null,
    },
    // Hidden until /api/login verifies users.two_factor_secret; otherwise 2FA is bypassable.
    {
        title: 'Appearance',
        href: '/settings/appearance',
        icon: null,
    },
];

export default function SettingsLayout({
    children,
}: Readonly<PropsWithChildren>) {
    // When server-side rendering, we only render the layout on the client...
    if (globalThis.window === undefined) {
        return null;
    }

    const currentPath = globalThis.window.location.pathname;

    return (
        <SettingsShell
            title="Settings"
            description="Manage your profile and account settings"
            items={sidebarNavItems.map((item) => ({
                title: item.title,
                href: item.href,
                icon: item.icon,
                active: currentPath === item.href,
            }))}
        >
            <div className="max-w-xl space-y-12">{children}</div>
        </SettingsShell>
    );
}
