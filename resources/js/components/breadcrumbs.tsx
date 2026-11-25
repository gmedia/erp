import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { useAuth } from '@/contexts/auth-context';
import { useTranslation } from '@/contexts/i18n-context';
import { resolveBreadcrumbs } from '@/lib/breadcrumb-trail';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Fragment } from 'react';
import { Link, useLocation } from 'react-router-dom';

export function Breadcrumbs({
    breadcrumbs,
}: Readonly<{
    breadcrumbs?: BreadcrumbItemType[];
}>) {
    const { menus } = useAuth();
    const { t } = useTranslation();
    const { pathname } = useLocation();
    const items = resolveBreadcrumbs(
        menus ?? [],
        pathname,
        breadcrumbs,
        (name, displayName) => {
            const key = `menu.${name}`;
            const translated = t(key);
            return translated === key ? displayName : translated;
        },
    );

    if (items.length === 0) {
        return null;
    }

    return (
        <Breadcrumb>
            <BreadcrumbList>
                {items.map((item, index) => {
                    const isLast = index === items.length - 1;
                    return (
                        <Fragment key={`${item.href}-${item.title}`}>
                            <BreadcrumbItem>
                                {isLast ? (
                                    <BreadcrumbPage>
                                        {item.title}
                                    </BreadcrumbPage>
                                ) : (
                                    <BreadcrumbLink asChild>
                                        <Link to={item.href}>{item.title}</Link>
                                    </BreadcrumbLink>
                                )}
                            </BreadcrumbItem>
                            {!isLast && <BreadcrumbSeparator />}
                        </Fragment>
                    );
                })}
            </BreadcrumbList>
        </Breadcrumb>
    );
}
