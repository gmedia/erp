import { cn } from '@/lib/utils';
import type { ReactNode } from 'react';

export type PageHeaderProps = {
    title: string;
    description?: string;
    actions?: ReactNode;
    className?: string;
    meta?: ReactNode;
};

export function PageHeader({
    title,
    description,
    actions,
    className,
    meta,
}: Readonly<PageHeaderProps>) {
    return (
        <div
            className={cn(
                'flex flex-col items-start justify-between gap-4 md:flex-row md:items-center',
                className,
            )}
        >
            <div className="min-w-0 space-y-1">
                <h1 className="text-2xl font-bold tracking-tight text-foreground">
                    {title}
                </h1>
                {description ? (
                    <p className="text-sm text-muted-foreground md:text-base">
                        {description}
                    </p>
                ) : null}
                {meta ? (
                    <div className="text-sm text-muted-foreground">{meta}</div>
                ) : null}
            </div>
            {actions ? (
                <div className="flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:items-center">
                    {actions}
                </div>
            ) : null}
        </div>
    );
}

export default PageHeader;
