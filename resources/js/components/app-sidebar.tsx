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

import { type MenuItem, type NavItem } from '@/types';
import { Link } from 'react-router-dom';
import { BookOpen, Folder } from 'lucide-react';
import AppLogo from './app-logo';
import { useAuth } from '@/contexts/auth-context';

export function AppSidebar() {
    const { t } = useTranslation();
    const { menus } = useAuth();
    
    const mainNavItems: NavItem[] = menus.map((menu) => ({
        title: menu.display_name,
        href: menu.url ?? '#',
        icon: getIcon(menu.icon),
        children: menu.children?.map((child) => ({
            title: child.display_name,
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
