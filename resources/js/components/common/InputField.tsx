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

interface InputFieldProps extends Omit<React.ComponentProps<'input'>, 'name' | 'prefix'> {
    name: string;
    label: string;
    placeholder?: string;
    className?: string;
    prefix?: ReactNode;
    suffix?: ReactNode;
    children?: ReactNode;
}

export function InputField({
    name,
    label,
    placeholder,
    type = 'text',
    className,
    prefix,
    suffix,
    children,
    ...props
}: InputFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    <FormLabel>{label}</FormLabel>
                    <div className="relative flex items-center">
                        {prefix && (
                            <div className="absolute left-3 flex items-center pointer-events-none text-muted-foreground select-none">
                                {prefix}
                            </div>
                        )}
                        <FormControl>
                            <Input
                                type={type}
                                placeholder={placeholder}
                                className={cn(prefix && "pl-10", suffix && "pr-10")}
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
                        {suffix && (
                            <div className="absolute right-3 flex items-center pointer-events-none text-muted-foreground select-none">
                                {suffix}
                            </div>
                        )}
                    </div>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
