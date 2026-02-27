import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
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
];

export default function AdminSettingsLayout({
    children,
    currentGroup = 'general',
}: AdminSettingsLayoutProps) {
    return (
        <div className="px-4 py-6">
            <Heading
                title="Admin Settings"
                description="Manage application-wide settings and preferences"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {sidebarNavItems.map((item, index) => {
                            const isActive =
                                (currentGroup === 'general' &&
                                    !item.href.includes('?')) ||
                                item.href.includes(`group=${currentGroup}`);

                            return (
                                <Button
                                    key={`${item.href}-${index}`}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn('w-full justify-start', {
                                        'bg-muted': isActive,
                                    })}
                                >
                                    <Link href={item.href}>
                                        {item.icon && (
                                            <item.icon className="h-4 w-4" />
                                        )}
                                        {item.title}
                                    </Link>
                                </Button>
                            );
                        })}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
