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
    readonly name: string;
    readonly label?: string;
    readonly url: string; // API URL
    readonly placeholder?: string;
    readonly className?: string;
    readonly children?: ReactNode;
    readonly labelFn?: (item: T) => string;
    readonly valueFn?: (item: T) => string;
    readonly initialLabel?: string;
    readonly onItemSelect?: (item: T) => void;
}

export default function AsyncSelectField<
    T extends object = Record<string, unknown>,
>({
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
}: Readonly<AsyncSelectFieldProps<T>>) {
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
