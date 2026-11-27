import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/contexts/i18n-context';
import { getIcon } from '@/lib/icon-map';

import { useAuth } from '@/contexts/auth-context';
import {
    rememberSidebarScroll,
    restoreSidebarScroll,
} from '@/lib/sidebar-scroll';
import { type NavItem } from '@/types';
import { useLayoutEffect } from 'react';
import { Link } from 'react-router-dom';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { t } = useTranslation();
    const { menus } = useAuth();

    useLayoutEffect(() => {
        const el = document.querySelector<HTMLElement>(
            '[data-sidebar="content"]',
        );
        if (!el) return;
        restoreSidebarScroll(el);
        const onScroll = () => rememberSidebarScroll(el);
        el.addEventListener('scroll', onScroll, { passive: true });
        return () => el.removeEventListener('scroll', onScroll);
    }, [menus]);

    const menuTitle = (name: string, displayName: string): string => {
        const key = `menu.${name}`;
        const translated = t(key);
        return translated === key ? displayName : translated;
    };

    const mainNavItems: NavItem[] = menus.map((menu) => ({
        title: menuTitle(menu.name, menu.display_name),
        href: menu.url ?? '#',
        icon: getIcon(menu.icon),
        children: menu.children?.map((child) => ({
            title: menuTitle(child.name, child.display_name),
            href: child.url ?? '#',
            icon: getIcon(child.icon),
        })),
    }));

    const footerNavItems: NavItem[] = [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link to="/dashboard">
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
