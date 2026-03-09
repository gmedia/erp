import { cn } from '@/lib/utils';
import { ComponentProps } from 'react';
import { Link } from 'react-router-dom';

type LinkProps = ComponentProps<typeof Link>;

export default function TextLink({
    className = '',
    children,
    ...props
}: LinkProps) {
    return (
        <Link
            className={cn(
                'text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500',
                className,
            )}
            {...props}
        >
            {children}
        </Link>
    );
}
