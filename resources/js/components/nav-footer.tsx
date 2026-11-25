import { Icon } from '@/components/icon';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import {
    SidebarGroup,
    SidebarGroupContent,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useTranslation } from '@/contexts/i18n-context';
import { type NavItem } from '@/types';
import { BookOpen } from 'lucide-react';
import { type ComponentPropsWithoutRef } from 'react';
import { Link } from 'react-router-dom';

export function NavFooter({
    items,
    className,
    ...props
}: ComponentPropsWithoutRef<typeof SidebarGroup> & {
    items: NavItem[];
}) {
    const { t } = useTranslation();

    return (
        <SidebarGroup
            {...props}
            className={`group-data-[collapsible=icon]:p-0 ${className || ''}`}
        >
            <SidebarGroupContent>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <LanguageSwitcher />
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton
                            asChild
                            className="text-muted-foreground hover:text-foreground"
                        >
                            <Link to="/user-guide">
                                <BookOpen className="h-5 w-5" />
                                <span className="group-data-[collapsible=icon]:hidden">
                                    {t('shell.user_guide')}
                                </span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    {items.map((item) => (
                        <SidebarMenuItem key={item.title}>
                            <SidebarMenuButton
                                asChild
                                className="text-muted-foreground hover:text-foreground"
                            >
                                <a
                                    href={item.href}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {item.icon && (
                                        <Icon
                                            iconNode={item.icon}
                                            className="h-5 w-5"
                                        />
                                    )}
                                    <span>{item.title}</span>
                                </a>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    ))}
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    );
}
