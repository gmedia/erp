'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import { type ReactNode } from 'react';
import { AsyncSelect } from './AsyncSelect';

interface AsyncSelectFieldProps<T extends object = Record<string, unknown>> {
    name: string;
    label?: string;
    url: string; // API URL
    placeholder?: string;
    className?: string;
    children?: ReactNode;
    labelFn?: (item: T) => string;
    valueFn?: (item: T) => string;
    initialLabel?: string;
    onItemSelect?: (item: any) => void;
}

export default function AsyncSelectField<T extends object = Record<string, unknown>>({
    name,
    label,
    url,
    placeholder = '',
    className,
    children,
    labelFn,
    valueFn,
    initialLabel,
    onItemSelect,
}: AsyncSelectFieldProps<T>) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    {label && <FormLabel>{label}</FormLabel>}
                    <FormControl>
                        <AsyncSelect<T>
                            value={
                                field.value ? String(field.value) : undefined
                            }
                            onValueChange={field.onChange}
                            url={url}
                            placeholder={placeholder}
                            labelFn={labelFn}
                            valueFn={valueFn}
                            initialLabel={initialLabel}
                            onItemSelect={onItemSelect}
                            label={label}
                        />
                    </FormControl>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
