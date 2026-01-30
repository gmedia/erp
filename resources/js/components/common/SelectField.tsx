'use client';

import {
    FormControl,
    FormField,
    FormItem,
    FormLabel,
    FormMessage,
} from '@/components/ui/form';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { type ReactNode } from 'react';

export interface Option {
    value: string;
    label: string;
}

interface SelectFieldProps {
    name: string;
    label?: string;
    options: Option[];
    placeholder?: string;
    className?: string;
    children?: ReactNode;
}

/**
 * Generic select component for Employee, Position, and Department forms.
 */
export default function SelectField({
    name,
    label,
    options,
    placeholder = '',
    className,
    children,
}: SelectFieldProps) {
    return (
        <FormField
            name={name}
            render={({ field }) => (
                <FormItem className={className}>
                    {label && <FormLabel>{label}</FormLabel>}
                    <FormControl>
                        <Select
                            onValueChange={field.onChange}
                            defaultValue={field.value}
                        >
                            <SelectTrigger className="w-full">
                                <span>
                                    {field.value
                                        ? options.find(
                                              (o) => o.value === field.value,
                                          )?.label
                                        : placeholder}
                                </span>
                            </SelectTrigger>
                            <SelectContent>
                                {options.map((opt) => (
                                    <SelectItem
                                        key={opt.value}
                                        value={opt.value}
                                    >
                                        {opt.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </FormControl>
                    <FormMessage />
                    {children}
                </FormItem>
            )}
        />
    );
}
