'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface TextareaFieldProps
    extends Omit<React.ComponentProps<'textarea'>, 'name'> {
    name: string;
    label: string;
    placeholder?: string;
    className?: string;
    children?: ReactNode;
    rows?: number;
}

export function TextareaField({
    name,
    label,
    placeholder,
    className,
    children,
    rows = 3,
    ...props
}: TextareaFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={cn('space-y-2', className)}>
                    <FormLabel>{label}</FormLabel>
                    <FormControl>
                        <Textarea
                            placeholder={placeholder}
                            value={field.value as string | undefined}
                            onChange={field.onChange}
                            onBlur={field.onBlur}
                            name={field.name}
                            ref={field.ref}
                            rows={rows}
                            {...props}
                        />
                    </FormControl>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
