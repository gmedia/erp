'use client';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { TableCell, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { ReactNode } from 'react';
import { Helmet } from 'react-helmet-async';

type PaginationMeta = {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
    from?: number;
    to?: number;
};

type PaginationView = {
    page: number;
    per_page: number;
    total: number;
    last_page: number;
    from: number;
    to: number;
};

type StandaloneTablePageProps = {
    title: string;
    breadcrumbs: BreadcrumbItem[];
    description: ReactNode;
    actions?: ReactNode;
    heading?: string;
    children: ReactNode;
};

type TableLoadingRowProps = {
    colSpan: number;
    message: string;
    icon: ReactNode;
};

type TableEmptyStateRowProps = {
    colSpan: number;
    title: string;
    description: ReactNode;
    icon: ReactNode;
    action?: ReactNode;
};

export function buildStandalonePaginationView(
    meta: PaginationMeta,
    dataLength: number,
): PaginationView {
    const from =
        meta.from ??
        (meta.total === 0 ? 0 : (meta.current_page - 1) * meta.per_page + 1);
    const to =
        meta.to ??
        (meta.total === 0
            ? 0
            : (meta.current_page - 1) * meta.per_page + dataLength);

    return {
        page: meta.current_page,
        per_page: meta.per_page,
        total: meta.total,
        last_page: meta.last_page,
        from,
        to,
    };
}

export function StandaloneTablePage({
    title,
    breadcrumbs,
    description,
    actions,
    heading = title,
    children,
}: Readonly<StandaloneTablePageProps>) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Helmet>
                <title>{title}</title>
            </Helmet>

            <div className="flex flex-col gap-6 p-6">
                <Card>
                    <CardHeader className="gap-4 sm:flex-row sm:items-start sm:justify-between sm:space-y-0">
                        <div>
                            <CardTitle className="text-2xl font-semibold tracking-tight">
                                {heading}
                            </CardTitle>
                            <CardDescription className="mt-1">
                                {description}
                            </CardDescription>
                        </div>

                        {actions && (
                            <div className="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center">
                                {actions}
                            </div>
                        )}
                    </CardHeader>

                    <CardContent className="flex flex-col gap-4">
                        {children}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

export function TableLoadingRow({
    colSpan,
    message,
    icon,
}: Readonly<TableLoadingRowProps>) {
    return (
        <TableRow>
            <TableCell colSpan={colSpan} className="h-56 text-center">
                <div className="flex flex-col items-center justify-center gap-2">
                    {icon}
                    <p className="text-muted-foreground">{message}</p>
                </div>
            </TableCell>
        </TableRow>
    );
}

export function TableEmptyStateRow({
    colSpan,
    title,
    description,
    icon,
    action,
}: Readonly<TableEmptyStateRowProps>) {
    return (
        <TableRow>
            <TableCell colSpan={colSpan} className="h-56 text-center">
                <div className="flex flex-col items-center justify-center gap-2 opacity-70">
                    {icon}
                    <p className="text-lg font-medium">{title}</p>
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                    {action}
                </div>
            </TableCell>
        </TableRow>
    );
}
