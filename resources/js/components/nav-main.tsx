import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/contexts/i18n-context';
import { type NavItem } from '@/types';
import { Link, useLocation } from 'react-router-dom';
import { ChevronRight } from 'lucide-react';
import { useAuth } from '@/contexts/auth-context';

import { Badge } from '@/components/ui/badge';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const { pendingApprovalsCount } = useAuth();
    const location = useLocation();
    const pendingCount = pendingApprovalsCount || 0;
    const { t } = useTranslation();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{t('nav.platform')}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) =>
                    item.children && item.children.length > 0 ? (
                        <Collapsible
                            key={item.title}
                            asChild
                            defaultOpen={item.children.some((child) =>
                                location.pathname.startsWith(child.href),
                            )}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton
                                        tooltip={{ children: item.title }}
                                    >
                                        {item.icon && <item.icon />}
                                        <span>{item.title}</span>
                                        <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.children.map((subItem) => (
                                            <SidebarMenuSubItem
                                                key={subItem.title}
                                            >
                                                <SidebarMenuSubButton
                                                    asChild
                                                    isActive={location.pathname.startsWith(
                                                        subItem.href,
                                                    )}
                                                >
                                                    <Link
                                                        to={subItem.href}
                                                    >
                                                        {subItem.icon && (
                                                            <subItem.icon />
                                                        )}
                                                        <span>
                                                            {subItem.title}
                                                        </span>
                                                        {subItem.href === '/my-approvals' && pendingCount > 0 && (
                                                            <Badge variant="destructive" className="ml-auto flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1 text-[10px]">
                                                                {pendingCount}
                                                            </Badge>
                                                        )}
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    ) : (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                isActive={location.pathname.startsWith(item.href)}
                                tooltip={{ children: item.title }}
                            >
                                <Link to={item.href}>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                    {item.href === '/my-approvals' && pendingCount > 0 && (
                                        <Badge variant="destructive" className="ml-auto flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1 text-[10px]">
                                            {pendingCount}
                                        </Badge>
                                    )}
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ),
                )}
            </SidebarMenu>
        </SidebarGroup>
    );
}
