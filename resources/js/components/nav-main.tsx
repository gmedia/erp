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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useAuth } from '@/contexts/auth-context';
import { useTranslation } from '@/contexts/i18n-context';
import { isNavHrefActive, pathMatchesNavHref } from '@/lib/nav-active';
import { type NavItem } from '@/types';
import { ChevronRight } from 'lucide-react';
import { useMemo } from 'react';
import { Link, useLocation } from 'react-router-dom';

import { Badge } from '@/components/ui/badge';

function collectNavHrefs(items: NavItem[]): string[] {
    const hrefs: string[] = [];

    for (const item of items) {
        if (item.href && item.href !== '#') {
            hrefs.push(item.href);
        }
        if (item.children?.length) {
            for (const child of item.children) {
                if (child.href && child.href !== '#') {
                    hrefs.push(child.href);
                }
            }
        }
    }

    return hrefs;
}

export function NavMain({ items = [] }: Readonly<{ items: NavItem[] }>) {
    const { pendingApprovalsCount } = useAuth();
    const location = useLocation();
    const pendingCount = pendingApprovalsCount || 0;
    const { t } = useTranslation();

    const allHrefs = useMemo(() => collectNavHrefs(items), [items]);

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
                                pathMatchesNavHref(
                                    location.pathname,
                                    child.href,
                                ),
                            )}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton
                                        size="sm"
                                        tooltip={{ children: item.title }}
                                    >
                                        {item.icon && <item.icon />}
                                        <span className="min-w-0 flex-1 truncate">
                                            {item.title}
                                        </span>
                                        <ChevronRight className="ml-auto size-4 shrink-0 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {item.children.map((subItem) => (
                                            <SidebarMenuSubItem
                                                key={subItem.title}
                                            >
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <SidebarMenuSubButton
                                                            asChild
                                                            size="sm"
                                                            isActive={isNavHrefActive(
                                                                location.pathname,
                                                                subItem.href,
                                                                allHrefs,
                                                            )}
                                                        >
                                                            <Link
                                                                to={
                                                                    subItem.href
                                                                }
                                                            >
                                                                {subItem.icon && (
                                                                    <subItem.icon />
                                                                )}
                                                                <span className="min-w-0 flex-1 truncate">
                                                                    {
                                                                        subItem.title
                                                                    }
                                                                </span>
                                                                {subItem.href ===
                                                                    '/my-approvals' &&
                                                                    pendingCount >
                                                                        0 && (
                                                                        <Badge
                                                                            variant="destructive"
                                                                            className="ml-auto flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1 text-[10px]"
                                                                        >
                                                                            {
                                                                                pendingCount
                                                                            }
                                                                        </Badge>
                                                                    )}
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </TooltipTrigger>
                                                    <TooltipContent
                                                        side="right"
                                                        align="center"
                                                    >
                                                        {subItem.title}
                                                    </TooltipContent>
                                                </Tooltip>
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
                                size="sm"
                                isActive={isNavHrefActive(
                                    location.pathname,
                                    item.href,
                                    allHrefs,
                                )}
                                tooltip={{ children: item.title }}
                            >
                                <Link to={item.href}>
                                    {item.icon && <item.icon />}
                                    <span className="min-w-0 flex-1 truncate">
                                        {item.title}
                                    </span>
                                    {item.href === '/my-approvals' &&
                                        pendingCount > 0 && (
                                            <Badge
                                                variant="destructive"
                                                className="ml-auto flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1 text-[10px]"
                                            >
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
