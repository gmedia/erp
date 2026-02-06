'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';
import { AsyncSelect } from './AsyncSelect';

interface AsyncSelectFieldProps {
    name: string;
    label?: string;
    url: string; // API URL
    placeholder?: string;
    className?: string;
    children?: ReactNode;
    labelFn?: (item: any) => string;
    valueFn?: (item: any) => string;
    initialLabel?: string;
}

export default function AsyncSelectField({
    name,
    label,
    url,
    placeholder = '',
    className,
    children,
    labelFn,
    valueFn,
    initialLabel,
}: AsyncSelectFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    {label && <FormLabel>{label}</FormLabel>}
                    <FormControl>
                        <AsyncSelect
                            value={field.value ? String(field.value) : undefined}
                            onValueChange={field.onChange}
                            url={url}
                            placeholder={placeholder}
                            labelFn={labelFn}
                            valueFn={valueFn}
                            initialLabel={initialLabel}
                        />
                    </FormControl>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
