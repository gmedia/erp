'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

interface InputFieldProps extends Omit<React.ComponentProps<'input'>, 'name'> {
    name: string;
    label: string;
    placeholder?: string;
    className?: string;
    children?: ReactNode;
}

export function InputField({
    name,
    label,
    placeholder,
    type = 'text',
    className,
    children,
    ...props
}: InputFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={cn('space-y-2', className)}>
                    <FormLabel>{label}</FormLabel>
                    <FormControl>
                        <Input
                            type={type}
                            placeholder={placeholder}
                            value={
                                field.value as
                                    | string
                                    | number
                                    | readonly string[]
                                    | undefined
                            }
                            onChange={field.onChange}
                            onBlur={field.onBlur}
                            name={field.name}
                            ref={field.ref}
                            {...props}
                        />
                    </FormControl>
                    {children}
                </FormItem>
            )}
        />
    );
}
